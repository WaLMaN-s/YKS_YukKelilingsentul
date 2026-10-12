<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'kasir' => 'Kasir',
            'staff' => 'Staff',
            'customer' => 'Customer',
        ] as $slug => $name) {
            Role::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
