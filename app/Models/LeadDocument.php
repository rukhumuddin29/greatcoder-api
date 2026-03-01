<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_id',
        'user_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'category'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
