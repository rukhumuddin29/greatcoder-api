<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MockInterview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'interviewer_id',
        'scheduled_at',
        'status',
        'technical_score',
        'behavioral_score',
        'notes'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime'
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
