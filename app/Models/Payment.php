<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\PaymentService;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'enrollment_id',
        'received_by',
        'amount',
        'payment_mode',
        'transaction_reference',
        'payment_date',
        'payment_type',
        'installment_number',
        'discount_amount',
        'discount_reason',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'discount_amount' => 'decimal:2',
        'installment_number' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->receipt_number = PaymentService::generateReceiptNumber();
        });
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class , 'received_by');
    }
}
