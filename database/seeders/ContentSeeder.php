<?php

namespace Database\Seeders;

use App\Models\Gallery;
use App\Models\Promo;
use App\Models\TourPackage;
use App\Models\TourSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        Promo::updateOrCreate(['code' => 'HEMAT20'], [
            'type' => 'percent', 'value' => 20, 'min_transaction' => 200000, 'max_discount' => 100000,
            'starts_at' => now()->startOfMonth(), 'ends_at' => now()->addMonths(2)->endOfMonth(), 'quota' => 100, 'used' => 0, 'is_active' => true,
        ]);
        Promo::updateOrCreate(['code' => 'WEEKEND50'], [
            'type' => 'fixed', 'value' => 50000, 'min_transaction' => 300000, 'max_discount' => null,
            'starts_at' => now()->startOfMonth(), 'ends_at' => now()->addMonth()->endOfMonth(), 'quota' => 50, 'used' => 0, 'is_active' => true,
        ]);

        // [nama, harga/orang, durasi, destinasi, gambar, fasilitas, bawa motor pribadi]
        $packs = [
            ['Paket Keliling Gunung Pancar', 350000, '1 Hari', 'Jalur hutan pinus & bukit Gunung Pancar', 'tour-sentul', ['Motor trail', 'Helm & perlengkapan', 'Guide berpengalaman', 'Dokumentasi foto', 'BBM', 'Makan siang'], false],
            ['Paket Keliling + Curug', 500000, '1 Hari', 'Gunung Pancar + Curug', 'tour-sunrise', ['Motor trail', 'Helm & perlengkapan', 'Guide berpengalaman', 'Tiket curug', 'Dokumentasi foto', 'BBM', 'Makan siang'], false],
            ['Paket Adventure Offroad', 550000, '1 Hari', 'Jalur offroad menantang & sungai kecil', 'tour-adventure', ['Motor trail', 'Helm & perlengkapan lengkap', 'Guide profesional', 'Dokumentasi foto & video', 'BBM', 'Makan siang', 'Asuransi perjalanan'], false],
            ['Paket Bawa Motor Pribadi', 150000, '1 Hari', 'Gunung Pancar (motor milik sendiri)', 'tour-adventure', ['Guide berpengalaman', 'Rute offroad Gunung Pancar', 'Dokumentasi foto', 'Akses basecamp (toilet, musola, parkir)'], true],
        ];
        foreach ($packs as [$name, $price, $duration, $dest, $img, $fac, $own]) {
            $pack = TourPackage::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'price' => $price, 'duration' => $duration, 'destination' => $dest,
                'description' => "{$name}: nikmati rute terbaik Sentul bersama guide lokal yang hafal setiap jalur. Cocok untuk pemula maupun rider berpengalaman. Tidak ada jadwal open trip yang cocok? Pilih Private Trip di tanggal Anda.",
                'facilities' => $fac, 'image' => "images/tours/{$img}.svg", 'quota' => 8, 'is_active' => true, 'own_motor' => $own,
            ]);
            $pack->schedules()->delete();
            foreach ([3, 7, 10, 14, 21] as $d) {
                TourSchedule::create(['tour_package_id' => $pack->id, 'date' => now()->addDays($d), 'quota' => 8, 'booked' => rand(0, 4), 'notes' => 'Kumpul 07.00 di basecamp']);
            }
        }

        $items = [
            ['Honda CRF 150L di jalur hutan', 'motor', 'g1'], ['KLX 150 siap berangkat', 'motor', 'g2'],
            ['Grup rider pagi hari', 'perjalanan', 'g3'], ['Sunrise di bukit Sentul', 'wisata', 'g4'],
            ['Konvoi akhir pekan', 'perjalanan', 'g5'], ['Rider puas di garis finish', 'customer', 'g6'],
            ['Jalur sungai kecil', 'wisata', 'g7'], ['KTM 250 EXC', 'motor', 'g8'],
        ];
        Gallery::query()->delete();
        foreach ($items as [$title, $cat, $img]) {
            Gallery::create(['title' => $title, 'category' => $cat, 'image' => "images/gallery/{$img}.svg", 'is_active' => true]);
        }
    }
}
