<?php

namespace App\Models;

use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enrollment_number',
        'lead_id',
        'course_id',
        'agreed_price',
        'start_date',
        'status',
        'enrolled_by',
        'notes',
        'discount_reason',
    ];

    protected $casts = [
        'agreed_price' => 'decimal:2',
        'start_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->enrollment_number = EnrollmentService::generateNumber();
        });
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function enrolledBy()
    {
        return $this->belongsTo(User::class , 'enrolled_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function mockInterviews()
    {
        return $this->hasMany(MockInterview::class);
    }

    public function studentInterviews()
    {
        return $this->hasMany(StudentInterview::class);
    }

    public function placement()
    {
        return $this->hasOne(Placement::class);
    }
}
