<?php

namespace Tests\Feature;

use App\Models\Motorcycle;
use App\Models\PaymentMethod;
use App\Models\Rental;
use App\Models\TourBooking;
use App\Models\TourPackage;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_seeded_fleet_matches_business_numbers(): void
    {
        $this->assertSame(70, (int) Motorcycle::where('vehicle_type', 'trail')->sum('stock'));
        $this->assertSame(120, (int) Motorcycle::where('vehicle_type', 'jeep')->sum('stock'));
        $this->assertSame(['Jeep Saja', 'Jeep + Trekking', 'Jeep Saja + Curug', 'Jeep + Trekking + Curug'], \App\Models\RentalPackage::active()->orderBy('sort_order')->pluck('name')->all());
    }

    public function test_new_public_and_customer_pages_load(): void
    {
        $jeep = Motorcycle::where('vehicle_type', 'jeep')->first();
        $tour = TourPackage::where('own_motor', false)->first();

        foreach (['/motor?jenis=jeep', '/motor?jenis=trail', "/motor/{$jeep->slug}", "/tentang-kami", '/kontak', '/faq'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/')->assertOk()->assertSee('Gunung Pancar')->assertSee('Jeep Adventure');

        $this->actingAs($this->user('customer@rentalmotor.test'));
        foreach (["/booking?motor={$jeep->slug}&paket=1", "/paket-wisata/{$tour->slug}/pesan", '/dashboard/wisata'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_jeep_quote_endpoint(): void
    {
        $this->actingAs($this->user('customer@rentalmotor.test'));
        $jeep = Motorcycle::where('vehicle_type', 'jeep')->first();
        $package = \App\Models\RentalPackage::where('name', 'Jeep + Trekking')->first();

        $this->postJson('/booking/quote', [
            'motor_id' => $jeep->id, 'start_date' => now()->addDays(60)->toDateString(), 'quantity' => 2, 'package_id' => $package->id, 'people' => 8,
        ])->assertOk()->assertJson(['available' => true, 'subtotal' => 3400000, 'days' => 1]);
    }

    public function test_customer_can_book_a_private_trip_pay_and_admin_confirms(): void
    {
        Storage::fake('local');
        $package = TourPackage::first();
        $method = PaymentMethod::where('type', 'bank')->first();

        $this->actingAs($this->user('customer@rentalmotor.test'));
        $this->post('/pesan-wisata', [
            'tour_package_id' => $package->id, 'trip_type' => 'private', 'trip_date' => now()->addDays(4)->toDateString(), 'people' => 3,
            'payment_method_id' => $method->id, 'contact_name' => 'Rizky', 'contact_phone' => '081234567890', 'agree' => 1,
        ])->assertRedirect();

        $booking = TourBooking::latest('id')->first();
        $this->assertSame('private', $booking->trip_type);
        $this->assertSame($package->price * 3, $booking->total);

        $this->get("/wisata/{$booking->id}")->assertOk();
        $this->post("/wisata/{$booking->id}/bukti", ['proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame('waiting_verification', $booking->fresh()->status);

        $this->actingAs($this->user('admin@rentalmotor.test'));
        $this->get('/admin/booking-wisata')->assertOk();
        $this->get("/admin/booking-wisata/{$booking->id}")->assertOk();
        $this->post("/admin/booking-wisata/{$booking->id}/verifikasi")->assertRedirect();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_customer_cannot_open_someone_elses_tour_booking(): void
    {
        $other = TourBooking::where('user_id', '!=', $this->user('customer@rentalmotor.test')->id)->first();

        $this->actingAs($this->user('customer@rentalmotor.test'));
        $this->get("/wisata/{$other->id}")->assertForbidden();
    }

    public function test_customer_can_reschedule_before_h_minus_2_and_forfeits_on_day_h(): void
    {
        $rental = Rental::where('status', 'confirmed')->where('source', 'website')->whereNull('package_id')->first();
        $this->actingAs($rental->user);

        $this->post("/dashboard/bookings/{$rental->id}/jadwal", ['start_date' => now()->addDays(40)->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(now()->addDays(40)->toDateString(), $rental->fresh()->start_date->toDateString());

        $rental->update(['start_date' => now()->startOfDay(), 'end_date' => now()->addDays($rental->days)->startOfDay()]);
        $this->post("/dashboard/bookings/{$rental->id}/jadwal", ['start_date' => now()->addDays(50)->toDateString()])->assertSessionHasErrors('start_date');

        $this->post("/dashboard/bookings/{$rental->id}/batal")->assertRedirect();
        $this->assertTrue($rental->fresh()->forfeited);
        $this->assertSame('cancelled', $rental->fresh()->status);
    }

    public function test_admin_can_record_a_manual_whatsapp_order_with_50_percent_down_payment(): void
    {
        $this->actingAs($this->user('admin@rentalmotor.test'));
        foreach (['/admin/penyewaan/manual', '/admin/paket-kendaraan', '/admin/addon', '/admin/booking-wisata'] as $url) {
            $this->get($url)->assertOk();
        }

        $motor = Motorcycle::where('vehicle_type', 'trail')->first();
        $this->post('/admin/penyewaan/manual', [
            'motor_id' => $motor->id, 'start_date' => now()->addDays(40)->toDateString(), 'end_date' => now()->addDays(42)->toDateString(),
            'quantity' => 2, 'customer_name' => 'Pak Budi', 'customer_phone' => '081299998888', 'source' => 'whatsapp',
            'payment_mode' => 'dp', 'payment_method_id' => PaymentMethod::first()->id,
        ])->assertRedirect();

        $rental = Rental::latest('id')->first();
        $this->assertSame('whatsapp', $rental->source);
        $this->assertSame(2, $rental->quantity);
        $this->assertSame('confirmed', $rental->status);
        $this->assertCount(2, $rental->payments);
        $this->assertSame($rental->total, (int) $rental->payments->sum('amount'));
        $this->assertSame((int) round($rental->total / 2), (int) $rental->payments->where('status', 'paid')->sum('amount'));
    }
}
