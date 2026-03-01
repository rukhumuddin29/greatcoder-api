<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Placement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'company_id',
        'ctc_annual',
        'join_date',
        'designation',
        'offer_letter_path',
        'student_photo'
    ];

    protected $casts = [
        'ctc_annual' => 'decimal:2',
        'join_date' => 'date'
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        return $this->student_photo ? url('storage/' . $this->student_photo) : null;
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function company()
    {
        return $this->belongsTo(HiringCompany::class, 'company_id');
    }
}
