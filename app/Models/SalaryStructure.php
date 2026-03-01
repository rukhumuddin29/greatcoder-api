<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    protected $fillable = [
        'user_id',
        'ctc_annual',
        'basic_salary',
        'hra',
        'da',
        'special_allowance',
        'pf_employer',
        'esi_employer',
        'other_allowances',
        'effective_from',
        'is_active'
    ];

    protected $casts = [
        'ctc_annual' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'hra' => 'decimal:2',
        'da' => 'decimal:2',
        'special_allowance' => 'decimal:2',
        'pf_employer' => 'decimal:2',
        'esi_employer' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Monthly gross = sum of all components (basic + hra + da + special + other)
     * This is the monthly CTC/12 minus employer-side contributions
     */
    public function getMonthlyGrossAttribute(): float
    {
        return round($this->basic_salary + $this->hra + $this->da + $this->special_allowance + $this->other_allowances, 2);
    }

    /**
     * Per day salary = monthly gross / 30
     */
    public function getPerDaySalaryAttribute(): float
    {
        return round($this->monthly_gross / 30, 2);
    }
}
