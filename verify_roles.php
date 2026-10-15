<?php

use App\Models\Motorcycle;
use App\Models\Rental;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

putenv('APP_ENV=testing'); // melewati CSRF, sama seperti test suite

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$app->instance(Vite::class, new class extends Vite
{
    public function __invoke($entrypoints, $buildDirectory = null)
    {
        return new HtmlString('');
    }

    public function __call($method, $parameters)
    {
        return '';
    }

    public function __toString()
    {
        return '';
    }
});

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$hit = function (string $method, string $uri, array $json = null) use ($kernel): array {
    $server = $json === null ? [] : ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
    $request = Request::create($uri, $method, [], [], [], $server, $json === null ? null : json_encode($json));
    $response = $kernel->handle($request);

    return [
        'status' => $response->getStatusCode(),
        'location' => $response->headers->get('Location') ?? '-',
        'body' => (string) $response->getContent(),
    ];
};

$line = function (string $label, string $uri, array $r, string $expect) {
    echo str_pad($label, 46).' '.str_pad('HTTP '.$r['status'], 9)
        .' | loc='.str_pad(substr((string) $r['location'], 0, 32), 34)
        .' | expect: '.$expect."\n";
};

$motor = Motorcycle::where('status', 'available')->first();
$tour = TourPackage::where('is_active', true)->first();

echo "data: motor={$motor->slug} | tour={$tour->slug}\n\n";

// ---------- TAMU ----------
echo "-- TAMU --\n";
$r = $hit('GET', '/booking');
$line('guest GET /booking', '/booking', $r, 'redirect /login');

// ---------- ADMIN ----------
echo "\n-- ADMIN --\n";
Illuminate\Support\Facades\Auth::login(User::where('email', 'admin@rentalmotor.test')->first());

$before = Rental::count();
$r = $hit('GET', "/booking?motor={$motor->slug}");
$line('admin GET /booking', "/booking?motor={$motor->slug}", $r, 'redirect /admin/dashboard');

$r = $hit('GET', '/dashboard');
$line('admin GET /dashboard', '/dashboard', $r, 'redirect /admin/dashboard');

$r = $hit('GET', "/paket-wisata/{$tour->slug}/pesan");
$line('admin GET pesan wisata', "/paket-wisata/{$tour->slug}/pesan", $r, 'redirect /admin/dashboard');

$r = $hit('POST', '/booking/quote', ['motor_id' => $motor->id, 'start_date' => now()->addDays(3)->toDateString()]);
$line('admin POST /booking/quote (json)', '/booking/quote', $r, 'HTTP 403 + json');

$r = $hit('POST', '/checkout');
$line('admin POST /checkout', '/checkout', $r, 'redirect /admin/dashboard');
echo '     rental_dibuat='.(Rental::count() - $before).' (harus 0)'.' | json_body='.substr(trim($r['body']), 0, 60)."\n";

$r = $hit('GET', '/admin/dashboard');
$line('admin GET /admin/dashboard', '/admin/dashboard', $r, 'HTTP 200');
$r = $hit('GET', '/admin/motor');
$line('admin GET /admin/motor', '/admin/motor', $r, 'HTTP 200');
$r = $hit('GET', '/notifikasi/99999');
$line('admin GET /notifikasi/{tak ada}', '/notifikasi/99999', $r, 'HTTP 404 (rute tetap terbuka)');

$r = $hit('GET', '/motor');
$line('admin GET /motor (publik)', '/motor', $r, '200 + tanpa tautan booking');
echo '     tautan_booking_di_kartu='.(str_contains($r['body'], '/booking?motor=') ? 'ADA (salah)' : 'tidak ada')."\n";

$r = $hit('GET', "/motor/{$motor->slug}");
$line('admin GET detail motor', "/motor/{$motor->slug}", $r, '200 + tanpa tombol Sewa');
echo '     tautan_booking='.(str_contains($r['body'], '/booking?motor=') ? 'ADA (salah)' : 'tidak ada')
    .' | notice_staf='.(str_contains($r['body'], 'pemesanan dari website hanya untuk akun customer') ? 'ya' : 'tidak')."\n";

$r = $hit('GET', "/paket-wisata/{$tour->slug}");
$line('admin GET detail wisata', "/paket-wisata/{$tour->slug}", $r, '200 + tanpa tombol Pesan');
echo '     tautan_pesan='.(str_contains($r['body'], '/pesan"') ? 'ADA (salah)' : 'tidak ada')."\n";

// ---------- CUSTOMER ----------
echo "\n-- CUSTOMER --\n";
Illuminate\Support\Facades\Auth::login(User::where('email', 'customer@rentalmotor.test')->first());

$r = $hit('GET', "/booking?motor={$motor->slug}");
$line('customer GET /booking', "/booking?motor={$motor->slug}", $r, 'HTTP 200');

$r = $hit('GET', '/dashboard');
$line('customer GET /dashboard', '/dashboard', $r, 'HTTP 200');

$r = $hit('GET', '/motor');
$line('customer GET /motor', '/motor', $r, '200 + tautan booking ada');
echo '     tautan_booking_di_kartu='.(str_contains($r['body'], '/booking?motor=') ? 'ada' : 'TIDAK ADA (salah)')."\n";

$r = $hit('GET', "/motor/{$motor->slug}");
$line('customer GET detail motor', "/motor/{$motor->slug}", $r, '200 + tombol Sewa ada');
echo '     tautan_booking='.(str_contains($r['body'], '/booking?motor=') ? 'ada' : 'TIDAK ADA (salah)')."\n";

$r = $hit('GET', "/paket-wisata/{$tour->slug}");
$line('customer GET detail wisata', "/paket-wisata/{$tour->slug}", $r, '200 + tombol Pesan ada');
echo '     tautan_pesan='.(str_contains($r['body'], "/{$tour->slug}/pesan") ? 'ada' : 'TIDAK ADA (salah)')."\n";

$r = $hit('GET', "/paket-wisata/{$tour->slug}/pesan");
$line('customer GET pesan wisata', "/paket-wisata/{$tour->slug}/pesan", $r, 'HTTP 200');
