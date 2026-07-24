<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';
    public const SUPER_STAFF = 'super_staff';
    public const COMPANY_ADMIN = 'company_admin';
    public const STAFF = 'staff';

    protected $fillable = ['name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function resolvesToAdminDashboard(): bool
    {
        return in_array($this->name, [self::SUPER_ADMIN, self::SUPER_STAFF], true);
    }

    public function resolvesToCompanyDashboard(): bool
    {
        return in_array($this->name, [self::COMPANY_ADMIN, self::STAFF], true);
    }

    public static function idForName(string $name): ?int
    {
        return static::query()->where('name', $name)->value('id');
    }
}