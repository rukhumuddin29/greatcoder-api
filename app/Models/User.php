<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasRolesAndPermissions;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'employee_id',
        'department',
        'designation',
        'salary',
        'status',
        'avatar',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'salary' => 'decimal:2',
        ];
    }

    public function salaryStructure()
    {
        return $this->hasOne(SalaryStructure::class)->where('is_active', true);
    }

    public function salaryStructures()
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function employeeDetail()
    {
        return $this->hasOne(EmployeeDetail::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
