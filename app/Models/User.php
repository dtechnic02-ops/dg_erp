<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Permission;
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
    'account_status',
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

   public function permissions()
{
    return $this->belongsToMany(
        Permission::class,
        'user_permissions'
    )
    ->withPivot('is_allowed')
    ->withTimestamps();
}

    // 🔐 Permission check
    public function hasPermission(string $permission): bool
{
    if (!auth()->check()) {
        return false;
    }

    // Super Admin
    if ((int) $this->role_id === Role::SUPER_ADMIN_ID) {
        return true;
    }
    if ((int) $this->role_id === Role::COMPANY_ADMIN_ID) {
    return true;
}

    // Super Staff receives only explicitly assigned platform permissions.
    if ((int) $this->role_id === Role::SUPER_STAFF_ID) {
        return $this->permissions()
            ->where('permissions.name', $permission)
            ->where('permissions.scope', Permission::SCOPE_PLATFORM)
            ->wherePivot('is_allowed', true)
            ->exists();
    }

    // Individual User Permission
    if (
        $this->permissions()
            ->where('permissions.name', $permission)
            ->wherePivot('is_allowed', true)
            ->exists()
    ) {
        return true;
    }

    // Role Permission
    if (
        $this->role &&
        $this->role->permissions()
            ->where('name', $permission)
            ->exists()
    ) {
        return true;
    }

    return false;
}

    
}
