<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentInterview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'company_id',
        'job_title',
        'status',
        'interview_date',
        'notes'
    ];

    protected $casts = [
        'interview_date' => 'date'
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function company()
    {
        return $this->belongsTo(HiringCompany::class, 'company_id');
    }
}
