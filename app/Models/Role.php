<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const SUPER_ADMIN_ID = 1;
    public const COMPANY_ADMIN_ID = 2;
    public const COMPANY_STAFF_ID = 3;
    public const SUPER_STAFF_ID = 4;
    public const COUNTRY_ADMIN_ID = 5;
    public const AUDITOR_ID = 6;

    protected $fillable = ['name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function resolvesToAdminDashboard(): bool
    {
        return in_array((int) $this->id, [self::SUPER_ADMIN_ID, self::COUNTRY_ADMIN_ID], true);
    }

    public function resolvesToCompanyDashboard(): bool
    {
        return in_array((int) $this->id, [self::COMPANY_ADMIN_ID, self::COMPANY_STAFF_ID, self::AUDITOR_ID], true);
    }
}
