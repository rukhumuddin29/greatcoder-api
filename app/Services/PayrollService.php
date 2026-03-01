<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class PayrollService extends BaseService
{
    /**
     * Calculate payroll for a single employee for a given month.
     * Uses the ÷ 30 method for per-day salary calculation.
     */
    public function calculateForEmployee(User $employee, string $month, int $generatedBy): array
    {
        $structure = SalaryStructure::where('user_id', $employee->id)
            ->where('is_active', true)
            ->first();

        if (!$structure) {
            throw new Exception("No active salary structure found for {$employee->name}");
        }

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth;

        // Get attendance records for the month
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Count attendance categories
        $daysPresent = $attendances->where('status', 'present')->count();
        $daysHalf = $attendances->where('status', 'half_day')->count();
        $daysAbsent = $attendances->where('status', 'absent')->count();
        $paidLeaves = $attendances->where('status', 'leave')->count();
        $holidays = $attendances->where('status', 'holiday')->count();
        $weekends = $attendances->where('status', 'weekend')->count();

        // Effective paid days = present + (half_day * 0.5) + paid leaves + holidays + weekends
        $effectiveWorkingDays = $daysPresent + ($daysHalf * 0.5) + $paidLeaves + $holidays + $weekends;

        // Loss of Pay days = 30 - effective paid days
        // If no attendance is marked at all, assume full month
        $totalMarkedDays = $attendances->count();
        if ($totalMarkedDays === 0) {
            // No attendance marked: treat as full month (all 30 days paid)
            $effectiveWorkingDays = 30;
            $daysAbsent = 0;
        }

        // Per day salary = Monthly Gross ÷ 30
        $monthlyGross = $structure->monthly_gross;
        $perDaySalary = round($monthlyGross / 30, 2);

        // Proration ratio
        $ratio = $effectiveWorkingDays / 30;

        // Earnings (prorated)
        $basicEarned = round($structure->basic_salary * $ratio, 2);
        $hraEarned = round($structure->hra * $ratio, 2);
        $daEarned = round($structure->da * $ratio, 2);
        $specialEarned = round($structure->special_allowance * $ratio, 2);
        $otherEarned = round($structure->other_allowances * $ratio, 2);
        $totalEarnings = round($basicEarned + $hraEarned + $daEarned + $specialEarned + $otherEarned, 2);

        // Employee PF = 12% of basic earned (capped at ₹1,800/month i.e. 12% of ₹15,000)
        $pfEmployee = round(min($basicEarned * 0.12, 1800), 2);

        // ESI Employee = 0.75% of gross (applicable if gross ≤ ₹21,000)
        $esiEmployee = 0;
        if ($totalEarnings <= 21000) {
            $esiEmployee = round($totalEarnings * 0.0075, 2);
        }

        // TDS placeholder (can be configured later)
        $tds = 0;

        $totalDeductions = round($pfEmployee + $esiEmployee + $tds, 2);
        $netSalary = round($totalEarnings - $totalDeductions, 2);

        return [
            'user_id' => $employee->id,
            'month' => $month,
            'total_days_in_month' => $totalDaysInMonth,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'days_half' => $daysHalf,
            'paid_leaves' => $paidLeaves,
            'holidays' => $holidays,
            'effective_working_days' => $effectiveWorkingDays,
            'gross_salary' => $monthlyGross,
            'basic_earned' => $basicEarned,
            'hra_earned' => $hraEarned,
            'da_earned' => $daEarned,
            'special_earned' => $specialEarned,
            'other_earned' => $otherEarned,
            'total_earnings' => $totalEarnings,
            'pf_employee' => $pfEmployee,
            'esi_employee' => $esiEmployee,
            'tds' => $tds,
            'other_deductions' => 0,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'status' => 'draft',
            'generated_by' => $generatedBy,
        ];
    }

    /**
     * Generate payroll for all active employees with salary structures.
     */
    public function generateForMonth(string $month, int $generatedBy): array
    {
        $employees = User::where('status', 'active')
            ->whereHas('salaryStructure', function ($q) {
            $q->where('is_active', true);
        })
            ->get();

        $results = [
            'generated' => [],
            'skipped' => [],
            'errors' => [],
        ];

        foreach ($employees as $employee) {
            // Check if payroll already exists for this month
            $existing = PayrollRun::where('user_id', $employee->id)
                ->where('month', $month)
                ->first();

            if ($existing) {
                $results['skipped'][] = [
                    'user_id' => $employee->id,
                    'name' => $employee->name,
                    'reason' => 'Payroll already exists (status: ' . $existing->status . ')'
                ];
                continue;
            }

            try {
                $data = $this->calculateForEmployee($employee, $month, $generatedBy);
                $payroll = PayrollRun::create($data);
                $results['generated'][] = $payroll;
            }
            catch (Exception $e) {
                $results['errors'][] = [
                    'user_id' => $employee->id,
                    'name' => $employee->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Approve a payroll run.
     */
    public function approve(PayrollRun $payroll, int $approvedBy): PayrollRun
    {
        if ($payroll->status !== 'draft') {
            throw new Exception('Only draft payrolls can be approved');
        }

        $payroll->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
        ]);

        return $payroll->fresh();
    }

    /**
     * Mark payroll as paid and create corresponding expense entry.
     */
    public function markPaid(PayrollRun $payroll, int $paidBy): PayrollRun
    {
        if ($payroll->status !== 'approved') {
            throw new Exception('Only approved payrolls can be marked as paid');
        }

        return $this->transactional(function () use ($payroll, $paidBy) {
            // Find or create "Salary" expense category
            $category = ExpenseCategory::firstOrCreate(
            ['name' => 'Salary'],
            ['description' => 'Employee and staff salaries', 'status' => 'active']
            );

            // Create expense entry
            $expense = Expense::create([
                'category_id' => $category->id,
                'created_by' => $paidBy,
                'approved_by' => $paidBy,
                'title' => 'Salary - ' . $payroll->user->name . ' (' . $payroll->month . ')',
                'description' => 'Monthly salary payout for ' . $payroll->month,
                'amount' => $payroll->net_salary,
                'expense_date' => now(),
                'payment_mode' => 'bank_transfer',
                'status' => 'approved',
                'payroll_user_id' => $payroll->user_id,
                'payroll_month' => $payroll->month,
            ]);

            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
                'expense_id' => $expense->id,
            ]);

            return $payroll->fresh();
        });
    }

    /**
     * Get attendance summary for an employee for a month.
     */
    public function getAttendanceSummary(int $userId, string $month): array
    {
        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'total_days' => $startDate->daysInMonth,
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'holiday' => $attendances->where('status', 'holiday')->count(),
            'weekend' => $attendances->where('status', 'weekend')->count(),
            'marked' => $attendances->count(),
        ];
    }
}
