<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'alternate_phone', 'address', 'city', 'state', 'pincode',
        'lead_type', 'source', 'referred_by',
        'school_name', 'tenth_year', 'tenth_board', 'tenth_percentage', 'tenth_grade',
        'inter_college', 'inter_year', 'inter_board', 'inter_stream', 'inter_percentage', 'inter_grade',
        'degree_college', 'degree_year', 'degree_name', 'degree_specialization', 'degree_university', 'degree_percentage', 'degree_grade',
        'pg_college', 'pg_year', 'pg_name', 'pg_specialization', 'pg_university', 'pg_percentage', 'pg_grade',
        'current_company', 'current_designation', 'experience_years', 'current_skills',
        'status', 'assigned_to', 'created_by', 'notes', 'follow_up_date', 'converted_at', 'interested_course_id'
    ];

    protected $casts = [
        'converted_at' => 'datetime',
        'follow_up_date' => 'date',
        'experience_years' => 'integer',
        'tenth_percentage' => 'decimal:2',
        'inter_percentage' => 'decimal:2',
        'degree_percentage' => 'decimal:2',
        'pg_percentage' => 'decimal:2',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class , 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class , 'created_by');
    }

    public function callLogs()
    {
        return $this->hasMany(LeadCallLog::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function interestedCourse()
    {
        return $this->belongsTo(Course::class , 'interested_course_id');
    }

    public function documents()
    {
        return $this->hasMany(LeadDocument::class);
    }
}
