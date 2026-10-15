<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Daftar halaman yang memakai pagination + jumlah barisnya di DB + per halaman.
$checks = [
    ['PUBLIK  /motor                ', App\Models\Motorcycle::count(), 9],
    ['ADMIN   /admin/motor          ', App\Models\Motorcycle::count(), 12],
    ['ADMIN   /admin/penyewaan      ', App\Models\Rental::count(), 15],
    ['ADMIN   /admin/customer       ', App\Models\Customer::count(), 15],
    ['ADMIN   /admin/pembayaran     ', App\Models\Payment::count(), 15],
    ['ADMIN   /admin/review         ', App\Models\Review::count(), 12],
    ['ADMIN   /admin/booking-wisata ', App\Models\TourBooking::count(), 15],
    ['ADMIN   /admin/kategori-motor ', App\Models\MotorCategory::count(), 15],
    ['ADMIN   /admin/lokasi         ', App\Models\Location::count(), 15],
    ['ADMIN   /admin/addon          ', App\Models\Addon::count(), 15],
    ['ADMIN   /admin/paket-kendaraan', App\Models\RentalPackage::count(), 15],
    ['ADMIN   /admin/galeri         ', App\Models\Gallery::count(), 15],
    ['ADMIN   /admin/promo          ', App\Models\Promo::count(), 15],
];

foreach ($checks as [$label, $total, $perPage]) {
    $pages = max(1, (int) ceil($total / $perPage));
    echo $label.' | baris='.str_pad((string) $total, 3)
        .' | per_halaman='.str_pad((string) $perPage, 3)
        .' | halaman='.$pages
        .' | render_pagination='.($pages > 1 ? 'YES  <-- dulu kena error' : 'tidak')."\n";
}
