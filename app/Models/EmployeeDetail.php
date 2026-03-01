<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'city',
        'pincode',
        'state',
        'country',
        'alternate_number',
        'emergency_contact_number',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
