<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'total_days_in_month',
        'days_present',
        'days_absent',
        'days_half',
        'paid_leaves',
        'holidays',
        'effective_working_days',
        'gross_salary',
        'basic_earned',
        'hra_earned',
        'da_earned',
        'special_earned',
        'other_earned',
        'total_earnings',
        'pf_employee',
        'esi_employee',
        'tds',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'status',
        'generated_by',
        'approved_by',
        'paid_at',
        'expense_id',
        'remarks'
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'basic_earned' => 'decimal:2',
        'hra_earned' => 'decimal:2',
        'da_earned' => 'decimal:2',
        'special_earned' => 'decimal:2',
        'other_earned' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'pf_employee' => 'decimal:2',
        'esi_employee' => 'decimal:2',
        'tds' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'days_present' => 'decimal:1',
        'days_absent' => 'decimal:1',
        'days_half' => 'decimal:1',
        'effective_working_days' => 'decimal:1',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class , 'generated_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class , 'approved_by');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
