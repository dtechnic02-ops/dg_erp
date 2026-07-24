<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const SCOPE_PLATFORM = 'platform';
    public const SCOPE_COMPANY = 'company';

    protected $fillable = ['name', 'scope'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_permission');
    }

    public function scopePlatform(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_PLATFORM);
    }

    public function scopeCompany(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_COMPANY);
    }
}
