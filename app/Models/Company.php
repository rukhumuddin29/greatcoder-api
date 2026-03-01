<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'phone',
        'email',
        'website',
        'facebook',
        'instagram',
        'youtube',
        'linkedin',
    ];
}
