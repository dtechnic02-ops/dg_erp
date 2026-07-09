<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
    'name',
    'email',
    'password',
    'role_id',
    'company_id',
    'job_role', 
    'online_status',
    'login_at',
    'logout_at',
    'last_seen', // 🔥 ADD THIS
];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 🔗 Role relation (VERY IMPORTANT)
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function company()
{
    return $this->belongsTo(\App\Models\Company::class, 'company_id');
}

    // 🔐 Permission check
    public function hasPermission($permission)
    {
        if (!auth()->check()) {
            return false;
        }

        // super admin
        if ($this->role_id == 1) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->permissions()
            ->where('name', $permission)
            ->exists();
    }
}