<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\TourBookingController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MotorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TourPackageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer website
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/motor', [MotorController::class, 'index'])->name('motors.index');
Route::get('/motor/{slug}', [MotorController::class, 'show'])->name('motors.show');
Route::get('/paket-wisata', [TourPackageController::class, 'index'])->name('tours.index');
Route::get('/paket-wisata/{slug}', [TourPackageController::class, 'show'])->name('tours.show');
Route::get('/galeri', [PageController::class, 'gallery'])->name('gallery');
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/logout', fn () => redirect()->route('home'));

Route::middleware(['auth', 'active'])->group(function () {
    // Booking → checkout → pembayaran
    Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking/quote', [BookingController::class, 'quote'])->middleware('throttle:60,1')->name('booking.quote');
    Route::post('/checkout', [BookingController::class, 'store'])->name('checkout.store');
    Route::get('/checkout', fn () => redirect()->route('motors.index'));
    Route::get('/payment/{rental}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{rental}/bukti', [PaymentController::class, 'uploadProof'])->name('payment.proof');

    // Booking wisata (open trip & private trip)
    Route::get('/paket-wisata/{slug}/pesan', [TourBookingController::class, 'create'])->name('tour-booking.create');
    Route::post('/pesan-wisata', [TourBookingController::class, 'store'])->name('tour-booking.store');
    Route::get('/wisata/{booking}', [TourBookingController::class, 'show'])->name('tour-booking.show');
    Route::post('/wisata/{booking}/bukti', [TourBookingController::class, 'uploadProof'])->name('tour-booking.proof');
    Route::post('/wisata/{booking}/jadwal', [TourBookingController::class, 'reschedule'])->name('tour-booking.reschedule');
    Route::post('/wisata/{booking}/batal', [TourBookingController::class, 'cancel'])->name('tour-booking.cancel');

    // Dashboard customer
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::put('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::get('/dashboard/bookings', [DashboardController::class, 'bookings'])->name('dashboard.bookings');
    Route::get('/dashboard/bookings/{rental}', [DashboardController::class, 'showBooking'])->name('dashboard.bookings.show');
    Route::post('/dashboard/bookings/{rental}/jadwal', [DashboardController::class, 'rescheduleBooking'])->name('dashboard.bookings.reschedule');
    Route::get('/dashboard/wisata', [DashboardController::class, 'tours'])->name('dashboard.tours');
    Route::post('/dashboard/bookings/{rental}/batal', [DashboardController::class, 'cancelBooking'])->name('dashboard.bookings.cancel');
    Route::get('/dashboard/payments', [DashboardController::class, 'payments'])->name('dashboard.payments');
    Route::get('/dashboard/reviews', [DashboardController::class, 'reviews'])->name('dashboard.reviews');
    Route::post('/dashboard/reviews/{rental}', [DashboardController::class, 'storeReview'])->name('dashboard.reviews.store');

    // Notifikasi & berkas privat (KTP/SIM/bukti bayar)
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/berkas/dokumen/{customer}/{type}', [FileController::class, 'document'])->name('files.document');
    Route::get('/berkas/bukti-wisata/{booking}', [FileController::class, 'tourProof'])->name('files.tour-proof');
    Route::get('/berkas/bukti/{payment}', [FileController::class, 'proof'])->name('files.proof');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->middleware('permission:dashboard')->name('dashboard');

    // Motor, kategori, lokasi
    Route::middleware('permission:motor')->group(function () {
        Route::resource('motor', Admin\MotorController::class);
        Route::post('motor/{motor}/status', [Admin\MotorController::class, 'status'])->name('motor.status');
        Route::delete('foto-motor/{image}', [Admin\MotorController::class, 'destroyImage'])->name('motor.image.destroy');
        Route::post('foto-motor/{image}/utama', [Admin\MotorController::class, 'primaryImage'])->name('motor.image.primary');
        Route::resource('kategori-motor', Admin\MotorCategoryController::class)->except('show');
        Route::resource('lokasi', Admin\LocationController::class)->except('show');
        Route::resource('paket-kendaraan', Admin\RentalPackageController::class)->except('show');
        Route::resource('addon', Admin\AddonController::class)->except('show');
    });

    // Penyewaan (pencatatan manual harus didefinisikan sebelum penyewaan/{rental})
    Route::middleware('permission:rental')->group(function () {
        Route::get('penyewaan/manual', [Admin\ManualBookingController::class, 'create'])->name('penyewaan.manual');
        Route::post('penyewaan/manual', [Admin\ManualBookingController::class, 'store'])->name('penyewaan.manual.store');
    });
    Route::middleware('permission:rental,pickup,return')->group(function () {
        Route::get('penyewaan', [Admin\RentalController::class, 'index'])->name('penyewaan.index');
        Route::get('penyewaan/{rental}', [Admin\RentalController::class, 'show'])->name('penyewaan.show');
    });
    Route::middleware('permission:rental')->group(function () {
        Route::get('penyewaan/{rental}/edit', [Admin\RentalController::class, 'edit'])->name('penyewaan.edit');
        Route::put('penyewaan/{rental}', [Admin\RentalController::class, 'update'])->name('penyewaan.update');
        Route::post('penyewaan/{rental}/konfirmasi', [Admin\RentalController::class, 'confirm'])->name('penyewaan.confirm');
        Route::post('penyewaan/{rental}/batal', [Admin\RentalController::class, 'cancel'])->name('penyewaan.cancel');
    });
    Route::post('penyewaan/{rental}/selesai', [Admin\RentalController::class, 'complete'])->middleware('permission:rental,return')->name('penyewaan.complete');
    Route::middleware('permission:pickup')->group(function () {
        Route::post('penyewaan/{rental}/siap-diambil', [Admin\RentalController::class, 'ready'])->name('penyewaan.ready');
        Route::post('penyewaan/{rental}/diambil', [Admin\RentalController::class, 'pickedUp'])->name('penyewaan.picked-up');
    });
    Route::middleware('permission:return')->group(function () {
        Route::get('penyewaan/{rental}/pengembalian', [Admin\RentalController::class, 'returnForm'])->name('penyewaan.return');
        Route::post('penyewaan/{rental}/pengembalian', [Admin\RentalController::class, 'processReturn'])->name('penyewaan.return.store');
    });

    // Pembayaran
    Route::middleware('permission:payment')->group(function () {
        Route::get('pembayaran', [Admin\PaymentController::class, 'index'])->name('pembayaran.index');
        Route::post('pembayaran/{payment}/verifikasi', [Admin\PaymentController::class, 'verify'])->name('pembayaran.verify');
        Route::post('pembayaran/{payment}/tolak', [Admin\PaymentController::class, 'reject'])->name('pembayaran.reject');
        Route::post('pembayaran/{payment}/refund', [Admin\PaymentController::class, 'refund'])->name('pembayaran.refund');
        Route::resource('metode-pembayaran', Admin\PaymentMethodController::class)->except('show');
    });

    // Customer
    Route::middleware('permission:customer')->group(function () {
        Route::get('customer', [Admin\CustomerController::class, 'index'])->name('customer.index');
        Route::get('customer/{customer}', [Admin\CustomerController::class, 'show'])->name('customer.show');
        Route::get('customer/{customer}/edit', [Admin\CustomerController::class, 'edit'])->name('customer.edit');
        Route::put('customer/{customer}', [Admin\CustomerController::class, 'update'])->name('customer.update');
        Route::post('customer/{customer}/blokir', [Admin\CustomerController::class, 'toggleBlock'])->name('customer.block');
    });

    // Wisata
    Route::middleware('permission:tour')->group(function () {
        Route::get('booking-wisata', [Admin\TourBookingController::class, 'index'])->name('booking-wisata.index');
        Route::get('booking-wisata/{booking}', [Admin\TourBookingController::class, 'show'])->name('booking-wisata.show');
        Route::post('booking-wisata/{booking}/verifikasi', [Admin\TourBookingController::class, 'verify'])->name('booking-wisata.verify');
        Route::post('booking-wisata/{booking}/tolak', [Admin\TourBookingController::class, 'reject'])->name('booking-wisata.reject');
        Route::post('booking-wisata/{booking}/selesai', [Admin\TourBookingController::class, 'complete'])->name('booking-wisata.complete');
        Route::post('booking-wisata/{booking}/batal', [Admin\TourBookingController::class, 'cancel'])->name('booking-wisata.cancel');
        Route::resource('paket-wisata', Admin\TourPackageController::class)->except('show');
        Route::resource('jadwal-wisata', Admin\TourScheduleController::class)->except('show');
    });

    Route::resource('promo', Admin\PromoController::class)->except('show')->middleware('permission:promo');
    Route::resource('galeri', Admin\GalleryController::class)->except('show')->middleware('permission:gallery');

    Route::middleware('permission:review')->group(function () {
        Route::get('review', [Admin\ReviewController::class, 'index'])->name('review.index');
        Route::post('review/{review}/setujui', [Admin\ReviewController::class, 'approve'])->name('review.approve');
        Route::post('review/{review}/tolak', [Admin\ReviewController::class, 'reject'])->name('review.reject');
        Route::delete('review/{review}', [Admin\ReviewController::class, 'destroy'])->name('review.destroy');
    });

    Route::middleware('permission:report')->group(function () {
        Route::get('laporan', [Admin\ReportController::class, 'index'])->name('laporan.index');
        Route::get('laporan/export', [Admin\ReportController::class, 'export'])->name('laporan.export');
    });

    Route::middleware('permission:settings')->group(function () {
        Route::get('pengaturan', [Admin\SettingController::class, 'edit'])->name('pengaturan.edit');
        Route::put('pengaturan', [Admin\SettingController::class, 'update'])->name('pengaturan.update');
    });

    Route::resource('admin-users', Admin\AdminUserController::class)->except('show')->middleware('permission:users');
});
