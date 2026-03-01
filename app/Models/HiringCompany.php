<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HiringCompany extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'industry',
        'location',
        'contact_person',
        'phone',
        'email'
    ];

    public function studentInterviews()
    {
        return $this->hasMany(StudentInterview::class, 'company_id');
    }

    public function placements()
    {
        return $this->hasMany(Placement::class, 'company_id');
    }
}
