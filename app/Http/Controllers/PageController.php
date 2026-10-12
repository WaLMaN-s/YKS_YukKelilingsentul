<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Motorcycle;
use App\Models\TourPackage;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function gallery()
    {
        return view('pages.gallery', [
            'items' => Gallery::where('is_active', true)->latest('id')->get(),
        ]);
    }

    public function about()
    {
        return view('pages.about', [
            'unitCounts' => ['trail' => (int) Motorcycle::visible()->ofType('trail')->sum('stock'), 'jeep' => (int) Motorcycle::visible()->ofType('jeep')->sum('stock')],
            'facilities' => preg_split('/\r\n|\r|\n/', (string) setting('basecamp_facilities', ''), -1, PREG_SPLIT_NO_EMPTY),
        ]);
    }

    public function contact()
    {
        return view('pages.contact', ['facilities' => preg_split('/\r\n|\r|\n/', (string) setting('basecamp_facilities', ''), -1, PREG_SPLIT_NO_EMPTY)]);
    }

    public function faq()
    {
        $days = (int) setting('reschedule_min_days', 2);
        $faqs = [
            ['Apa saja syarat menyewa?', 'KTP dan SIM C asli yang masih berlaku, usia minimal '.setting('min_age', 17).' tahun. Jeep dan paket wisata tidak memerlukan SIM bagi penumpang.'],
            ['Bagaimana cara booking?', 'Pilih kendaraan atau paket wisata, tentukan tanggal, isi data, lalu bayar lunas via transfer/QRIS dan unggah bukti. Admin memverifikasi dan mengonfirmasi booking Anda.'],
            ['Apakah harus DP?', 'Pembayaran di website langsung lunas, tanpa DP. DP '.setting('dp_percent', 50).'% hanya berlaku untuk pemesanan manual lewat WhatsApp/Instagram yang dicatat admin.'],
            ['Kalau sedang tidak ada open trip, apakah tetap bisa booking?', 'Bisa. Pilih Private Trip dan tentukan tanggal sendiri (minimal besok) untuk paket wisata apa pun.'],
            ['Berapa harga jeep dan apa bedanya paket jeep?', 'Harga per jeep (maks 4 orang), tergantung paket: Jeep saja atau Jeep + Trekking. Tiket curug sudah menjadi bagian dari paket, bukan tambahan di luar. Lihat harga lengkap di halaman Jeep.'],
            ['Apa saja add-on yang tersedia?', 'Contoh: sepatu, guide, dan paket protector. Pilih saat booking; harga tertera jelas sebelum Anda membayar.'],
            ['Bisa membawa motor pribadi?', 'Bisa, melalui paket wisata “Bawa Motor Pribadi” (guide, rute, dan fasilitas basecamp tetap kami sediakan).'],
            ['Bisakah mengubah jadwal?', 'Bisa, paling lambat H-'.$days.' dari tanggal sewa/trip, selama unit atau kuota masih tersedia. Ubah langsung dari dashboard.'],
            ['Bagaimana jika membatalkan?', 'Pembatalan pada hari H membuat pembayaran hangus. Pembatalan sebelum hari H diproses admin (refund sesuai kesepakatan).'],
            ['Siapa yang menanggung kerusakan?', 'Kerusakan yang terjadi selama pemakaian ditanggung penyewa. Biaya dicatat saat pengembalian dan dipotong dari deposit.'],
            ['Terlambat mengembalikan kendaraan?', 'Dikenakan denda per jam sebesar '.rupiah(setting('late_fee_per_hour', 20000)).'.'],
            ['Fasilitas apa saja di basecamp?', 'Basecamp Gunung Pancar: '.implode(', ', array_map('strtolower', preg_split('/\r\n|\r|\n/', (string) setting('basecamp_facilities', 'toilet, musola, warung, parkir motor, parkir mobil'), -1, PREG_SPLIT_NO_EMPTY))).'.'],
        ];

        return view('pages.faq', ['faqs' => $faqs]);
    }

    public function sitemap(): Response
    {
        $urls = collect([route('home'), route('motors.index'), route('tours.index'), route('gallery'), route('about'), route('contact'), route('faq')])
            ->merge(Motorcycle::visible()->pluck('slug')->map(fn ($s) => route('motors.show', $s)))
            ->merge(TourPackage::where('is_active', true)->pluck('slug')->map(fn ($s) => route('tours.show', $s)));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url).'</loc></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
