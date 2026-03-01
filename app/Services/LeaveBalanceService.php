<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\WorkdaySetting;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveBalanceService extends BaseService
{
    /**
     * Get leave balances for a user for a given year.
     */
    public function getBalances(int $userId, int $year): array
    {
        $policy = $this->getPolicy();
        $user = User::findOrFail($userId);
        $joinDate = $user->created_at ? Carbon::parse($user->created_at) : Carbon::now()->startOfYear();
        
        $results = [];
        $leaveTypes = ['sick_leave', 'casual_leave', 'earned_leave'];

        foreach ($leaveTypes as $type) {
            $typePolicy = $policy[$type] ?? null;
            if (!$typePolicy) {
                $results[$type] = ['total' => 0, 'used' => 0, 'available' => 0];
                continue;
            }

            $annualQuota = $typePolicy['annual_quota'] ?? 0;
            
            // 1. Pro-rate for mid-year joiners (if joined this year)
            if ($joinDate->year === $year && $joinDate->month > 1) {
                $remainingMonths = 12 - $joinDate->month + 1;
                $annualQuota = round(($annualQuota / 12) * $remainingMonths, 1);
            }

            // 2. Used leaves in this year
            $used = LeaveRequest::where('user_id', $userId)
                ->where('leave_type', $type)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->sum('total_days');

            // 3. Carry forward (only for earned_leave typically)
            $carryForward = 0;
            if ($type === 'earned_leave' && ($typePolicy['carry_forward'] ?? false)) {
                // Simplified: Logic for previous year unused would go here
                // For now, we'll assume 0 or can be configured per user
            }

            $results[$type] = [
                'label' => $typePolicy['label'] ?? ucwords(str_replace('_', ' ', $type)),
                'total' => $annualQuota + $carryForward,
                'used' => (float)$used,
                'available' => max(0, ($annualQuota + $carryForward) - $used),
                'allow_half_day' => $typePolicy['allow_half_day'] ?? false
            ];
        }

        return $results;
    }

    /**
     * Calculate working days between two dates, excluding weekends and holidays.
     */
    public function calculateWorkingDays(string $start, string $end, bool $isHalfDay = false): float
    {
        if ($isHalfDay) return 0.5;

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        
        $weekends = $this->getWeekends();
        $holidays = Holiday::whereBetween('holiday_date', [$startDate, $endDate])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $period = CarbonPeriod::create($startDate, $endDate);
        $count = 0;

        foreach ($period as $date) {
            // Check if weekend
            if (in_array($date->dayOfWeek, $weekends)) continue;
            
            // Check if holiday
            if (in_array($date->toDateString(), $holidays)) continue;

            $count++;
        }

        return (float)$count;
    }

    /**
     * Get the leave policy from WorkdaySetting.
     */
    public function getPolicy(): array
    {
        $setting = WorkdaySetting::where('key', 'leave_policy')->first();
        if (!$setting) {
            return [
                'sick_leave' => ['annual_quota' => 12, 'allow_half_day' => true, 'label' => 'Sick Leave'],
                'casual_leave' => ['annual_quota' => 12, 'allow_half_day' => true, 'label' => 'Casual Leave'],
                'earned_leave' => ['annual_quota' => 15, 'allow_half_day' => false, 'carry_forward' => true, 'carry_forward_max' => 15, 'label' => 'Earned Leave'],
            ];
        }
        return $setting->value;
    }

    private function getWeekends(): array
    {
        $setting = WorkdaySetting::where('key', 'weekends')->first();
        return $setting ? $setting->value : [0]; // Default Sunday
    }
}
