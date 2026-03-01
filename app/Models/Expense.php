<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\ExpenseService;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expense_number',
        'category_id',
        'created_by',
        'approved_by',
        'title',
        'description',
        'amount',
        'expense_date',
        'payment_mode',
        'receipt_path',
        'status',
        'rejection_reason',
        'payroll_user_id',
        'payroll_month'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->expense_number = ExpenseService::generateNumber();
        });
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class , 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class , 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class , 'approved_by');
    }

    public function payrollUser()
    {
        return $this->belongsTo(User::class , 'payroll_user_id');
    }
}
