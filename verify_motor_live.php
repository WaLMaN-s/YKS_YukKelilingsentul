<?php

use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Stub Vite agar render tidak bergantung pada manifest build (hanya untuk verifikasi server-side).
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

$motor = App\Models\Motorcycle::first();
echo 'total_motor_di_db='.App\Models\Motorcycle::count()
    .' | contoh_slug='.($motor->slug ?? '-')
    .' | contoh_id='.($motor->id ?? '-')."\n";

$admin = App\Models\User::where('email', 'admin@rentalmotor.test')->first();
if ($admin) {
    Illuminate\Support\Facades\Auth::login($admin);
    echo 'login_admin=ok'."\n";
}

$uris = array_filter([
    '/motor',
    '/motor?page=2',
    $motor ? '/motor/'.$motor->slug : null,
    '/admin/motor',
    '/admin/motor?page=2',
    $motor ? '/admin/motor/'.$motor->id : null,
    $motor ? '/admin/motor/'.$motor->id.'/edit' : null,
]);

foreach ($uris as $uri) {
    try {
        $response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Request::create($uri, 'GET'));
        $body = $response->getContent();

        echo str_pad($uri, 34).' => HTTP '.$response->getStatusCode()
            .' | pagination='.(str_contains($body, 'aria-label="Pagination"') ? 'yes' : 'no')
            .' | ada_error_view='.(str_contains($body, 'elements does not exist') ? 'YA' : 'tidak')
            ."\n";
    } catch (Throwable $e) {
        echo str_pad($uri, 34).' => ERROR: '.get_class($e).': '.$e->getMessage()."\n";
    }
}
