<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Location;
use App\Models\Motorcycle;
use App\Models\MotorCategory;
use App\Models\PaymentMethod;
use App\Models\RentalPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $cats = collect([
            ['Trail 150cc', 'Motor trail ringan untuk pemula dan jalur menengah.'],
            ['Trail 250cc', 'Motor trail bertenaga untuk jalur berat dan pengendara berpengalaman.'],
            ['Dual Sport', 'Nyaman untuk jalan raya sekaligus jalur ringan.'],
            ['Jeep Offroad', 'Jeep offroad dengan driver untuk keluarga dan rombongan (maks 4 orang).'],
        ])->mapWithKeys(fn ($c) => [$c[0] => MotorCategory::updateOrCreate(['slug' => Str::slug($c[0])], ['name' => $c[0], 'description' => $c[1]])]);

        $facilities = ['Helm full face', 'Jas hujan', 'Pelindung lutut & siku', 'Sarung tangan', 'Kotak P3K', 'STNK & surat lengkap'];
        $terms = "KTP dan SIM C asli yang masih berlaku.\nDeposit dikembalikan saat motor kembali dalam kondisi baik.\nBBM ditanggung penyewa.\nKerusakan yang terjadi selama pemakaian ditanggung penyewa.\nDilarang dipindahtangankan ke pihak lain.\nPerubahan jadwal maksimal H-2; pembatalan pada hari H membuat pembayaran hangus.";

        // Motor trail: total 70 unit.
        // [nama, merek, model, kategori, cc, harian, mingguan, bulanan, deposit, warna, populer, promo, tahun, gambar, unit]
        $trails = [
            ['Honda CRF 150L', 'Honda', 'CRF150L', 'Trail 150cc', 150, 250000, 1500000, 5500000, 500000, 'Merah', true, false, 2024, 'crf150l', 15],
            ['Kawasaki KLX 150', 'Kawasaki', 'KLX 150', 'Trail 150cc', 150, 250000, 1500000, 5500000, 500000, 'Hijau', true, true, 2023, 'klx150', 15],
            ['Yamaha WR 155R', 'Yamaha', 'WR 155R', 'Trail 150cc', 155, 300000, 1800000, 6500000, 600000, 'Biru', true, false, 2024, 'wr155r', 10],
            ['KTM 250 EXC', 'KTM', 'EXC 250', 'Trail 250cc', 250, 400000, 2400000, 8800000, 1000000, 'Oranye', true, false, 2023, 'ktm250', 6],
            ['Honda CRF 250L', 'Honda', 'CRF250L', 'Dual Sport', 250, 350000, 2100000, 7700000, 800000, 'Merah', false, false, 2024, 'crf250l', 8],
            ['Kawasaki KLX 230', 'Kawasaki', 'KLX 230', 'Dual Sport', 230, 320000, 1900000, 7000000, 700000, 'Hijau', false, true, 2024, 'klx230', 6],
            ['Yamaha WR 250 F', 'Yamaha', 'WR 250 F', 'Trail 250cc', 250, 450000, 2700000, 9900000, 1000000, 'Biru', false, false, 2023, 'wr250', 4],
            ['Kawasaki D-Tracker 150', 'Kawasaki', 'D-Tracker 150', 'Dual Sport', 150, 230000, 1400000, 5000000, 500000, 'Hitam', false, false, 2022, 'dtracker', 4],
            ['Honda CRF 150L Lite', 'Honda', 'CRF150L', 'Trail 150cc', 150, 240000, 1450000, 5300000, 500000, 'Putih', false, false, 2022, 'crf150l-w', 2],
        ];
        foreach ($trails as [$name, $brand, $model, $cat, $cc, $day, $week, $month, $deposit, $color, $popular, $promo, $year, $img, $units]) {
            $motor = Motorcycle::updateOrCreate(['slug' => Str::slug($name)], [
                'vehicle_type' => 'trail', 'stock' => $units,
                'motor_category_id' => $cats[$cat]->id,
                'name' => $name, 'brand' => $brand, 'model' => $model, 'plate_number' => null,
                'year' => $year, 'cc' => $cc, 'transmission' => 'Manual', 'capacity' => 1, 'color' => $color,
                'price_daily' => $day, 'price_weekly' => $week, 'price_monthly' => $month, 'deposit' => $deposit,
                'description' => "{$name} siap menemani petualangan offroad Anda di jalur Gunung Pancar. Mesin {$cc}cc yang responsif, suspensi panjang, dan perawatan rutin sebelum setiap penyewaan.",
                'facilities' => $facilities, 'terms' => $terms, 'status' => 'available',
                'is_popular' => $popular, 'is_promo' => $promo,
            ]);
            $motor->images()->delete();
            foreach ([0, 1] as $n) {
                $motor->images()->create(['path' => "images/motors/{$img}.svg", 'is_primary' => $n === 0, 'sort_order' => $n]);
            }
        }

        // Jeep: total 120 unit, maks 4 orang per jeep. Harga final mengikuti paket (lihat di bawah).
        $jeepTerms = "Maksimal 4 orang per jeep.\nHarga sesuai paket yang dipilih, termasuk jeep dan driver.\nKerusakan yang terjadi selama pemakaian ditanggung penyewa.\nPerubahan jadwal maksimal H-2; pembatalan pada hari H membuat pembayaran hangus.";
        $jeeps = [
            ['Jeep Willys Classic', 'Willys', 'Willys CJ', 60, 'Hijau Army', true, 'jeep-willys'],
            ['Jeep Hardtop 4x4', 'Jeep', 'Hardtop', 40, 'Hitam', false, 'jeep-hardtop'],
            ['Jeep Offroad Premium', 'Jeep', 'Wrangler Offroad', 20, 'Oranye', false, 'jeep-premium'],
        ];
        foreach ($jeeps as [$name, $brand, $model, $units, $color, $popular, $img]) {
            $jeep = Motorcycle::updateOrCreate(['slug' => Str::slug($name)], [
                'vehicle_type' => 'jeep', 'stock' => $units,
                'motor_category_id' => $cats['Jeep Offroad']->id,
                'name' => $name, 'brand' => $brand, 'model' => $model, 'plate_number' => null,
                'year' => 2022, 'cc' => 2400, 'transmission' => 'Manual', 'capacity' => 4, 'color' => $color,
                'price_daily' => 1100000, 'price_weekly' => null, 'price_monthly' => null, 'deposit' => 0,
                'description' => "{$name}: jeep offroad lengkap dengan driver berpengalaman untuk menjelajah Gunung Pancar. Muat hingga 4 orang. Pilih paket Jeep saja atau Jeep + Trekking.",
                'facilities' => ['Driver berpengalaman', 'Maksimal 4 orang', 'Rute offroad Gunung Pancar', 'Air mineral'],
                'terms' => $jeepTerms, 'status' => 'available', 'is_popular' => $popular, 'is_promo' => false,
            ]);
            $jeep->images()->delete();
            foreach ([0, 1] as $n) {
                $jeep->images()->create(['path' => "images/motors/{$img}.svg", 'is_primary' => $n === 0, 'sort_order' => $n]);
            }
        }

        // Paket jeep. Curug dijadikan bagian paket (+Rp150.000), bukan add-on terpisah.
        foreach ([
            [1, 'Jeep Saja', 1100000, ['Jeep + driver', 'Maks 4 orang', 'Rute offroad Gunung Pancar']],
            [2, 'Jeep + Trekking', 1700000, ['Jeep + driver', 'Guide trekking', 'Maks 4 orang', 'Rute offroad + trekking']],
            [3, 'Jeep Saja + Curug', 1250000, ['Jeep + driver', 'Maks 4 orang', 'Rute offroad Gunung Pancar', 'Tiket curug']],
            [4, 'Jeep + Trekking + Curug', 1850000, ['Jeep + driver', 'Guide trekking', 'Maks 4 orang', 'Rute offroad + trekking', 'Tiket curug']],
        ] as [$order, $name, $price, $includes]) {
            RentalPackage::updateOrCreate(['vehicle_type' => 'jeep', 'name' => $name], ['price' => $price, 'includes' => $includes, 'sort_order' => $order, 'is_active' => true]);
        }

        // Add-on
        foreach ([
            ['Sepatu', 100000, 'person', 'Sepatu riding/trekking (sewa)'],
            ['Guide', 200000, 'booking', 'Pemandu tambahan selama perjalanan'],
            ['Paket Protector', 250000, 'person', 'Set pelindung badan (lutut, siku, dada)'],
        ] as [$name, $price, $per, $desc]) {
            Addon::updateOrCreate(['name' => $name], ['price' => $price, 'per' => $per, 'applies_to' => 'both', 'description' => $desc, 'is_active' => true]);
        }

        foreach ([
            ['Basecamp Gunung Pancar', 'Basecamp Gunung Pancar, Sentul, Bogor (ambil di basecamp)', 0],
            ['Sentul City', 'Antar ke area Sentul City', 50000],
            ['Stasiun Bogor', 'Antar ke Stasiun Bogor', 100000],
            ['Stasiun Cilebut', 'Antar ke Stasiun Cilebut', 100000],
        ] as [$name, $address, $fee]) {
            Location::updateOrCreate(['name' => $name], ['address' => $address, 'delivery_fee' => $fee, 'is_active' => true]);
        }

        foreach ([
            ['BCA', 'bank', 'Yuk Keliling Sentul', '1234567890', 'Transfer sesuai total pembayaran (lunas), lalu unggah bukti transfer.'],
            ['Mandiri', 'bank', 'Yuk Keliling Sentul', '9876543210', 'Transfer sesuai total pembayaran (lunas), lalu unggah bukti transfer.'],
            ['QRIS', 'qris', 'Yuk Keliling Sentul', 'NMID-ID1234567890', 'Scan QRIS yang ditampilkan admin di basecamp atau kirim via WhatsApp, lalu unggah bukti.'],
            ['Cash', 'cash', null, null, 'Bayar tunai saat pengambilan di basecamp. Booking dikonfirmasi admin.'],
        ] as [$name, $type, $acc, $no, $ins]) {
            PaymentMethod::updateOrCreate(['name' => $name], ['type' => $type, 'account_name' => $acc, 'account_number' => $no, 'instructions' => $ins, 'is_active' => true]);
        }
    }
}
