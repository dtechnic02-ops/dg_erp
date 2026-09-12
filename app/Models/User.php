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
    'country_id',
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

    public function countryMaster()
    {
        return $this->belongsTo(Country::class, 'country_id');
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
    public function hasPermission(string $permission, ?int $companyId = null): bool
{
    $scope = Permission::query()->where('name', $permission)->value('scope');
    if ($scope === Permission::SCOPE_PLATFORM) {
        return app(\App\Services\PlatformAuthorizationService::class)->can($this, $permission);
    }
    if ($scope === Permission::SCOPE_COMPANY) {
        return app(\App\Services\CompanyAuthorizationService::class)->can($this, $permission, $companyId ?? $this->company_id);
    }
    return false;
}

    
}
