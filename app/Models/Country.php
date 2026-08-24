<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Country extends Model
{
    protected $fillable = ['name', 'iso_code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Country $country): void {
            if (($country->exists && $country->getOriginal('iso_code') === 'NP') || $country->iso_code === 'NP') {
                if ($country->name !== 'Nepal' || $country->iso_code !== 'NP' || ! $country->is_active) {
                    throw ValidationException::withMessages([
                        'iso_code' => 'Canonical Nepal must remain Nepal / NP and active.',
                    ]);
                }
            }
        });
    }

    public function setIsoCodeAttribute(string $value): void
    {
        $this->attributes['iso_code'] = strtoupper(trim($value));
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function registrations()
    {
        return $this->hasMany(CompanyRegistration::class);
    }
}
