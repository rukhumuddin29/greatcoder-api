<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadCallLog extends Model
{
    protected $fillable = [
        'lead_id',
        'called_by',
        'channel',
        'call_outcome',
        'notes',
        'call_duration_seconds',
        'next_follow_up'
    ];

    protected $casts = [
        'next_follow_up' => 'date',
        'call_duration_seconds' => 'integer',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function calledByUser()
    {
        return $this->belongsTo(User::class , 'called_by');
    }
}
