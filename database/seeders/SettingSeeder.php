<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put([
            'site_name' => 'Yuk Keliling Sentul',
            'tagline' => 'Sewa Motor Trail, Jeep & Paket Adventure',
            'whatsapp' => config('services.whatsapp.number') ?: '081210842032',
            'email' => 'halo@yukkelilingsentul.test',
            'address' => 'Basecamp Gunung Pancar, Sentul, Bogor, Jawa Barat',
            'basecamp_facilities' => "Toilet\nMusola\nWarung\nParkir motor\nParkir mobil",
            'instagram' => 'yuk_keliling_sentul',
            'facebook' => 'yukkelilingsentul',
            'tiktok' => 'yuk_keliling_sentul',
            'operating_hours' => 'Setiap hari, 07.00 - 18.00 WIB',
            'min_age' => '17',
            'min_rental_days' => '1',
            'reschedule_min_days' => '2',
            'dp_percent' => '50',
            'default_deposit' => '300000',
            'late_fee_per_hour' => '25000',
            'return_time' => '17:00',
        ]);
    }
}
