<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'company_admin']);
        Role::create(['name' => 'staff']);
    }
}