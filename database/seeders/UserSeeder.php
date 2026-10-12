<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $role = fn (string $slug) => Role::where('slug', $slug)->value('id');

        // Admin utama (Super Admin) sesuai spesifikasi.
        User::updateOrCreate(['email' => 'admin@rentalmotor.test'], [
            'name' => 'Admin', 'phone' => '081210842032', 'password' => 'password', 'role_id' => $role('super_admin'),
        ]);
        User::updateOrCreate(['email' => 'operasional@rentalmotor.test'], [
            'name' => 'Dimas Operasional', 'phone' => '081200000001', 'password' => 'password', 'role_id' => $role('admin'),
        ]);
        User::updateOrCreate(['email' => 'kasir@rentalmotor.test'], [
            'name' => 'Sari Kasir', 'phone' => '081200000002', 'password' => 'password', 'role_id' => $role('kasir'),
        ]);
        User::updateOrCreate(['email' => 'staff@rentalmotor.test'], [
            'name' => 'Budi Staff', 'phone' => '081200000003', 'password' => 'password', 'role_id' => $role('staff'),
        ]);

        $customers = [
            ['customer@rentalmotor.test', 'Rizky Pratama', '081234567801'],
            ['dinda@rentalmotor.test', 'Dinda Sari', '081234567802'],
            ['andi@rentalmotor.test', 'Andi Saputra', '081234567803'],
            ['rudi@rentalmotor.test', 'Rudi Santoso', '081234567804'],
            ['siti@rentalmotor.test', 'Siti Nurhaliza', '081234567805'],
        ];
        foreach ($customers as $i => [$email, $name, $phone]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, 'phone' => $phone, 'password' => 'password', 'role_id' => $role('customer'),
            ]);
            Customer::updateOrCreate(['user_id' => $user->id], [
                'nik' => '32010'.str_pad((string) (1000000000 + $i * 111111), 11, '0', STR_PAD_LEFT),
                'address' => 'Jl. Contoh No. '.($i + 10).', Bogor, Jawa Barat',
                'birth_date' => '199'.($i + 1).'-0'.($i + 1).'-15',
                'sim_number' => '9901'.str_pad((string) (200000 + $i * 777), 8, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
