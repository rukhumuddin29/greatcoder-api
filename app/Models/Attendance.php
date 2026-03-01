<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'status',
        'check_in',
        'check_out',
        'check_in_ip',
        'check_out_ip',
        'check_in_lat',
        'check_in_lng',
        'check_out_lat',
        'check_out_lng',
        'working_hours',
        'late_minutes',
        'overtime_minutes',
        'source',
        'remarks',
        'marked_by'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markedByUser()
    {
        return $this->belongsTo(User::class , 'marked_by');
    }
}
