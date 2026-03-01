<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'mode',
        'duration_weeks',
        'total_sessions',
        'original_price',
        'offer_price',
        'is_negotiable',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_negotiable' => 'boolean',
        'original_price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'duration_weeks' => 'integer',
        'total_sessions' => 'integer',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class , 'created_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
