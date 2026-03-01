<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'module',
        'description'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class , 'role_permission');
    }

    public function users()
    {
        return $this->belongsToMany(User::class , 'user_permission')
            ->withPivot('granted');
    }
}
