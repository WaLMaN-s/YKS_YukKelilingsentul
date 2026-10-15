<?php

use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\HtmlString;

putenv('APP_ENV=testing');
putenv('APP_KEY=base64:'.base64_encode(str_repeat('a', 32)));
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('MAIL_MAILER=log');

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Stub Vite (public/build belum ada di repo ini), sama seperti withoutVite() pada test suite.
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

Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);

$admin = App\Models\User::where('email', 'admin@rentalmotor.test')->first();
if ($admin) {
    Illuminate\Support\Facades\Auth::login($admin);
    echo 'login_admin=yes'."\n";
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

foreach (['/motor', '/motor?page=2', '/motor?jenis=jeep&urut=termurah', '/admin/motor'] as $uri) {
    try {
        $response = $kernel->handle(Request::create($uri, 'GET'));
        $body = $response->getContent();

        echo $uri.' => HTTP '.$response->getStatusCode()
            .' | nav_pagination='.(str_contains($body, 'aria-label="Pagination"') ? 'yes' : 'no')
            .' | halaman_aktif='.(str_contains($body, 'aria-current="page"') ? 'yes' : 'no')
            .' | "motor ditemukan"='.(str_contains($body, 'motor ditemukan') ? 'yes' : 'no')
            ."\n";
    } catch (Throwable $e) {
        echo $uri.' => ERROR: '.get_class($e).': '.$e->getMessage()."\n";
    }
}
