<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'is_half_day',
        'half_day_type',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'admin_remarks',
        'responded_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'responded_at' => 'datetime',
        'is_half_day' => 'boolean',
        'total_days' => 'decimal:1'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser($query, $id)
    {
        return $query->where('user_id', $id);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('start_date', $year);
    }
}
