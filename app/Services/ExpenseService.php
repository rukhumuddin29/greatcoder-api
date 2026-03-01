<?php

namespace App\Services;

use App\Models\Expense;
use Carbon\Carbon;

class ExpenseService extends BaseService
{
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $count = Expense::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        return "EXP-{$year}{$month}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function record(array $data, int $userId)
    {
        return $this->transactional(function () use ($data, $userId) {
            return Expense::create(array_merge($data, [
                'created_by' => $userId,
                'status' => 'pending',
                'expense_date' => $data['expense_date'] ?? now(),
            ]));
        });
    }

    public function approve(Expense $expense, int $userId)
    {
        return $this->transactional(function () use ($expense, $userId) {
            $expense->update([
                'status' => 'approved',
                'approved_by' => $userId
            ]);
            return $expense;
        });
    }

    public function reject(Expense $expense, string $reason, int $userId)
    {
        return $this->transactional(function () use ($expense, $reason, $userId) {
            $expense->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => $userId
            ]);
            return $expense;
        });
    }
}
