<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LeaveService extends BaseService
{
    protected $balanceService;

    public function __construct(LeaveBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Apply for leave.
     */
    public function apply(int $userId, array $data): LeaveRequest
    {
        return $this->transactional(function () use ($userId, $data) {
            $type = $data['leave_type'];
            $start = $data['start_date'];
            $end = $data['end_date'];
            $isHalf = $data['is_half_day'] ?? false;
            
            // 1. Calculate working days
            $totalDays = $this->balanceService->calculateWorkingDays($start, $end, $isHalf);
            if ($totalDays <= 0) {
                throw new Exception("The selected dates are non-working days (weekends/holidays).");
            }

            // 2. Check for balance
            $balances = $this->balanceService->getBalances($userId, Carbon::parse($start)->year);
            $available = $balances[$type]['available'] ?? 0;
            if ($totalDays > $available) {
                throw new Exception("Insufficient leave balance. Requested {$totalDays} days, available {$available}.");
            }

            // 3. Check for overlaps
            $overlap = LeaveRequest::where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'rejected')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(fn($q) => $q->where('start_date', '<', $start)->where('end_date', '>', $end));
                })
                ->exists();
                
            if ($overlap) {
                throw new Exception("You already have a leave request covering these dates.");
            }

            // 4. Create request
            $leave = LeaveRequest::create(array_merge($data, [
                'user_id' => $userId,
                'total_days' => $totalDays,
                'status' => 'pending'
            ]));

            $this->logActivity('leave.applied', $leave);

            return $leave;
        });
    }

    /**
     * Approve a leave request.
     */
    public function approve(LeaveRequest $leave, int $approvedBy, ?string $remarks = null): LeaveRequest
    {
        if ($leave->status !== 'pending') {
            throw new Exception("Only pending requests can be approved.");
        }

        return $this->transactional(function () use ($leave, $approvedBy, $remarks) {
            // Update request
            $leave->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'admin_remarks' => $remarks,
                'responded_at' => now()
            ]);

            // Sync with attendance table
            $dates = $this->getWorkingDates($leave->start_date, $leave->end_date);
            foreach ($dates as $date) {
                Attendance::updateOrCreate(
                    ['user_id' => $leave->user_id, 'date' => $date],
                    [
                        'status' => 'leave',
                        'remarks' => $leave->leave_type, // e.g. "sick_leave"
                        'marked_by' => $approvedBy
                    ]
                );
            }

            $this->logActivity('leave.approved', $leave);

            return $leave;
        });
    }

    public function reject(LeaveRequest $leave, int $approvedBy, ?string $remarks = null): LeaveRequest
    {
        if ($leave->status !== 'pending') {
            throw new Exception("Only pending requests can be rejected.");
        }

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'admin_remarks' => $remarks,
            'responded_at' => now()
        ]);

        $this->logActivity('leave.rejected', $leave);

        return $leave;
    }

    public function cancel(LeaveRequest $leave, int $userId): LeaveRequest
    {
        if ($leave->user_id !== $userId) {
            throw new Exception("Unauthorized cancellation.");
        }
        
        if ($leave->status === 'approved' && Carbon::parse($leave->start_date)->isPast()) {
            throw new Exception("Cannot cancel approved leave that has already started.");
        }

        return $this->transactional(function () use ($leave) {
            $oldStatus = $leave->status;
            $leave->update(['status' => 'cancelled']);

            // If it was already approved, remove attendance marks
            if ($oldStatus === 'approved') {
                Attendance::where('user_id', $leave->user_id)
                    ->whereBetween('date', [$leave->start_date, $leave->end_date])
                    ->where('status', 'leave')
                    ->delete();
            }

            $this->logActivity('leave.cancelled', $leave);

            return $leave;
        });
    }

    private function getWorkingDates($start, $end): array
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $dates = [];

        // Simple loop for now (not excluding weekends here because balance already handles it)
        // But for attendance we should only mark working days
        // Re-use logic from BalanceService to get exact list of working days
        $policy = app(LeaveBalanceService::class);
        $setting = WorkdaySetting::where('key', 'weekends')->first();
        $weekends = $setting ? $setting->value : [0];
        
        $holidays = \App\Models\Holiday::whereBetween('holiday_date', [$startDate, $endDate])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if (!in_array($current->dayOfWeek, $weekends) && !in_array($current->toDateString(), $holidays)) {
                $dates[] = $current->toDateString();
            }
            $current->addDay();
        }

        return $dates;
    }
}
