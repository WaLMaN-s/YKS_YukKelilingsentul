<?php

use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

putenv('APP_ENV=testing');

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

Illuminate\Support\Facades\Auth::login(App\Models\User::where('email', 'admin@rentalmotor.test')->first());
$motor = App\Models\Motorcycle::where('status', 'available')->first();

$request = Request::create('/booking/quote', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
], json_encode(['motor_id' => $motor->id, 'start_date' => now()->addDays(3)->toDateString()]));

$response = $kernel->handle($request);
echo 'quote_staf: HTTP '.$response->getStatusCode()
    .' | content-type='.$response->headers->get('Content-Type')
    .' | body='.trim((string) $response->getContent())."\n";

$r = $kernel->handle(Request::create('/admin/penyewaan/manual', 'GET'));
echo 'admin GET /admin/penyewaan/manual => HTTP '.$r->getStatusCode().' (menu catat pesanan manual tetap jalan)'."\n";

$r = $kernel->handle(Request::create('/admin/paket-wisata', 'GET'));
echo 'admin GET /admin/paket-wisata => HTTP '.$r->getStatusCode()."\n";
