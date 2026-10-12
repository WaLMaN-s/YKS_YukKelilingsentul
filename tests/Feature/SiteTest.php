<?php

namespace Tests\Feature;

use App\Models\Motorcycle;
use App\Models\Rental;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_load(): void
    {
        $slug = Motorcycle::first()->slug;

        foreach (['/', '/motor', "/motor/{$slug}", '/paket-wisata', '/galeri', '/tentang-kami', '/kontak', '/faq', '/login', '/register', '/sitemap.xml'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_motor_filters_and_availability_search(): void
    {
        $this->get('/motor?urut=termurah&cc=gt200')->assertOk();
        $this->get('/motor?start='.now()->addDays(30)->toDateString().'&end='.now()->addDays(32)->toDateString())->assertOk();
    }

    public function test_unknown_page_uses_custom_404(): void
    {
        $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_guests_are_redirected_from_private_areas(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/booking')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_customer_area_and_booking_page(): void
    {
        $customer = User::where('email', 'customer@rentalmotor.test')->first();
        $motor = Motorcycle::where('status', 'available')->first();

        $this->actingAs($customer);
        foreach (['/dashboard', '/dashboard/profile', '/dashboard/bookings', '/dashboard/payments', '/dashboard/reviews', "/booking?motor={$motor->slug}"] as $url) {
            $this->get($url)->assertOk();
        }

        $rental = Rental::where('user_id', $customer->id)->first();
        $this->get("/dashboard/bookings/{$rental->id}")->assertOk();
        $this->get("/payment/{$rental->id}")->assertOk();
    }

    public function test_customer_cannot_open_other_customers_booking_or_admin(): void
    {
        $customer = User::where('email', 'customer@rentalmotor.test')->first();
        $other = Rental::where('user_id', '!=', $customer->id)->first();

        $this->actingAs($customer);
        $this->get("/dashboard/bookings/{$other->id}")->assertForbidden();
        $this->get("/payment/{$other->id}")->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_login_works_and_wrong_password_fails(): void
    {
        $this->post('/login', ['email' => 'admin@rentalmotor.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'admin@rentalmotor.test', 'password' => 'password'])->assertRedirect('/admin/dashboard');
    }

    public function test_super_admin_can_open_every_admin_page(): void
    {
        $this->actingAs(User::where('email', 'admin@rentalmotor.test')->first());
        $rental = Rental::where('status', 'rented')->first();

        $pages = [
            '/admin/dashboard', '/admin/motor', '/admin/motor/create', '/admin/kategori-motor', '/admin/kategori-motor/create',
            '/admin/lokasi', '/admin/promo', '/admin/promo/create', '/admin/galeri', '/admin/paket-wisata', '/admin/jadwal-wisata',
            '/admin/metode-pembayaran', '/admin/penyewaan', "/admin/penyewaan/{$rental->id}", "/admin/penyewaan/{$rental->id}/pengembalian",
            "/admin/penyewaan/{$rental->id}/edit", '/admin/pembayaran', '/admin/customer', '/admin/review', '/admin/laporan',
            '/admin/laporan?type=rental&range=year', '/admin/laporan/export?type=pendapatan&format=csv', '/admin/laporan/export?type=denda&format=print',
            '/admin/pengaturan', '/admin/admin-users', '/admin/admin-users/create',
        ];
        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/motor/'.Motorcycle::first()->id)->assertOk();
        $this->get('/admin/motor/'.Motorcycle::first()->id.'/edit')->assertOk();
        $this->get('/admin/customer/'.User::where('email', 'customer@rentalmotor.test')->value('id'))->assertOk();
    }

    public function test_role_permissions_are_enforced(): void
    {
        $this->actingAs(User::where('email', 'kasir@rentalmotor.test')->first());
        $this->get('/admin/penyewaan')->assertOk();
        $this->get('/admin/pembayaran')->assertOk();
        $this->get('/admin/motor')->assertForbidden();
        $this->get('/admin/pengaturan')->assertForbidden();
        $this->get('/admin/admin-users')->assertForbidden();

        $this->actingAs(User::where('email', 'staff@rentalmotor.test')->first());
        $this->get('/admin/motor')->assertOk();
        $this->get('/admin/pembayaran')->assertForbidden();
        $this->get('/admin/laporan')->assertForbidden();

        $this->actingAs(User::where('email', 'operasional@rentalmotor.test')->first());
        $this->get('/admin/laporan')->assertOk();
        $this->get('/admin/pengaturan')->assertForbidden();
    }

    public function test_admin_workflow_confirm_pickup_return_complete(): void
    {
        $this->actingAs(User::where('email', 'admin@rentalmotor.test')->first());
        $rental = Rental::where('status', 'confirmed')->first();

        $this->post("/admin/penyewaan/{$rental->id}/diambil")->assertRedirect();
        $this->assertSame('rented', $rental->fresh()->status);

        $this->post("/admin/penyewaan/{$rental->id}/pengembalian", [
            'returned_at' => $rental->end_date->copy()->setTime(19, 0)->format('Y-m-d\TH:i'),
            'condition' => 'ringan', 'fuel_level' => '1/2', 'damage_fee' => 50000,
        ])->assertRedirect();

        $fresh = $rental->fresh();
        $this->assertSame('returned', $fresh->status);
        $this->assertSame(2, $fresh->returnRecord->late_hours);
        $this->assertSame(2 * 25000 + 50000, $fresh->extra_fee);

        $this->post("/admin/penyewaan/{$rental->id}/selesai")->assertRedirect();
        $this->assertSame('completed', $fresh->fresh()->status);
    }

    public function test_payment_verification_moves_rental_to_paid(): void
    {
        $this->actingAs(User::where('email', 'kasir@rentalmotor.test')->first());
        $rental = Rental::where('status', 'waiting_payment')->first();

        $this->post('/admin/pembayaran/'.$rental->payment->id.'/verifikasi')->assertRedirect();

        $this->assertSame('paid', $rental->fresh()->status);
        $this->assertSame('paid', $rental->payment->fresh()->status);
    }

    public function test_review_needs_admin_approval_before_it_shows(): void
    {
        $this->get('/')->assertDontSee('Motor terawat, proses cepat');   // review berstatus pending

        $this->actingAs(User::where('email', 'admin@rentalmotor.test')->first());
        $review = \App\Models\Review::where('status', 'pending')->first();
        $this->post("/admin/review/{$review->id}/setujui")->assertRedirect();

        auth()->logout();
        $this->assertSame('approved', $review->fresh()->status);
    }
}
