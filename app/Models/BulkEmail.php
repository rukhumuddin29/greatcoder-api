<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkEmail extends Model
{
    protected $fillable = [
        'subject',
        'body',
        'type',
        'target_status',
        'recipient',
        'recipients_count',
        'sent_by',
        'status'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class , 'sent_by');
    }
}
