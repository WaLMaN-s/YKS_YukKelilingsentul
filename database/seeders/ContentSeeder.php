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

        // Fasilitas (include) dan hal yang belum termasuk (exclude) berlaku untuk semua paket.
        $facilities = [
            'Guide / Leader', 'BBM sesuai rute', 'Helm + sepatu safety', 'Jersey set (dipinjamkan)',
            'Jalur trabas', 'Air mineral 600ml', 'Dokumentasi (HP)',
        ];
        $excludes = ['Baju ganti', 'Tas wadah minum', 'Sarung tangan', 'Uang cash', 'Tipping guide / leader'];

        // [nama, harga/orang, minimal peserta, destinasi, gambar, bawa motor pribadi]
        $packs = [
            ['Paket Trail 150 CC', 700000, 2, 'Jalur trabas Gunung Pancar', 'tour-sentul', false],
            ['Paket Trail KLX 140 CC', 1300000, 1, 'Jalur trabas Gunung Pancar', 'tour-sunrise', false],
            ['Paket Trail 300 CC', 2400000, 1, 'Jalur trabas Gunung Pancar', 'tour-adventure', false],
        ];

        // Buang paket lama yang tidak lagi ditawarkan (mis. dari seed sebelumnya).
        TourPackage::whereNotIn('slug', array_map(fn ($p) => Str::slug($p[0]), $packs))->delete();

        foreach ($packs as [$name, $price, $minPeople, $dest, $img, $own]) {
            $pack = TourPackage::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'price' => $price, 'duration' => '1 Hari', 'destination' => $dest,
                'description' => "{$name}: nikmati rute terbaik Sentul bersama guide lokal yang hafal setiap jalur. Cocok untuk pemula maupun rider berpengalaman. Tidak ada jadwal open trip yang cocok? Pilih Private Trip di tanggal Anda.",
                'facilities' => $facilities, 'excludes' => $excludes, 'image' => "images/tours/{$img}.svg",
                'quota' => 8, 'min_participants' => $minPeople, 'is_active' => true, 'own_motor' => $own,
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
