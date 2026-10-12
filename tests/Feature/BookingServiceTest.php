<?php

namespace Tests\Feature;

use App\Models\Motorcycle;
use App\Models\PaymentMethod;
use App\Models\Promo;
use App\Models\Rental;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function bookingData(string $start, string $end, array $extra = []): array
    {
        return $extra + [
            'start_date' => $start, 'end_date' => $end,
            'renter_name' => 'Tester', 'renter_phone' => '081234567890', 'renter_email' => 'tester@example.com',
        ];
    }

    private function method(): PaymentMethod
    {
        return PaymentMethod::create(['name' => 'BCA', 'type' => 'bank', 'is_active' => true]);
    }

    public function test_weekly_and_monthly_rates_are_applied_when_cheaper(): void
    {
        $motor = Motorcycle::factory()->create(['price_daily' => 100000, 'price_weekly' => 500000, 'price_monthly' => 1800000]);

        $this->assertSame(300000, $this->service()->priceFor($motor, 3));
        $this->assertSame(500000, $this->service()->priceFor($motor, 7));
        $this->assertSame(600000, $this->service()->priceFor($motor, 8));
        $this->assertSame(1800000, $this->service()->priceFor($motor, 30));
    }

    public function test_quote_adds_deposit_and_delivery_and_subtracts_discount(): void
    {
        $motor = Motorcycle::factory()->create(['price_daily' => 250000, 'deposit' => 500000]);
        Promo::create(['code' => 'HEMAT20', 'type' => 'percent', 'value' => 20, 'min_transaction' => 200000, 'max_discount' => 100000, 'quota' => 10, 'is_active' => true]);

        $quote = $this->service()->quote($motor, Carbon::parse('2030-01-10'), Carbon::parse('2030-01-12'), 'hemat20');

        $this->assertSame(2, $quote['days']);
        $this->assertSame(500000, $quote['subtotal']);
        $this->assertSame(100000, $quote['discount']); // 20% = 100.000 (tepat di batas maksimal)
        $this->assertSame(500000 + 500000 - 100000, $quote['total']);
        $this->assertTrue($quote['available']);
    }

    public function test_promo_rules(): void
    {
        $expired = Promo::create(['code' => 'OLD', 'type' => 'percent', 'value' => 10, 'ends_at' => now()->subDay(), 'is_active' => true]);
        $full = Promo::create(['code' => 'FULL', 'type' => 'fixed', 'value' => 5000, 'quota' => 1, 'used' => 1, 'is_active' => true]);
        $min = Promo::create(['code' => 'MIN', 'type' => 'fixed', 'value' => 5000, 'min_transaction' => 500000, 'is_active' => true]);

        $this->assertFalse($expired->check(300000)[0]);
        $this->assertFalse($full->check(300000)[0]);
        $this->assertFalse($min->check(300000)[0]);
        $this->assertTrue($min->check(600000)[0]);
    }

    public function test_overlap_is_detected_but_back_to_back_is_allowed(): void
    {
        $motor = Motorcycle::factory()->create();
        $user = User::factory()->create();
        $this->service()->create($user, $motor, $this->method(), $this->bookingData('2030-02-10', '2030-02-13'));

        $s = $this->service();
        $this->assertTrue($s->hasOverlap($motor, Carbon::parse('2030-02-11'), Carbon::parse('2030-02-12')));
        $this->assertTrue($s->hasOverlap($motor, Carbon::parse('2030-02-09'), Carbon::parse('2030-02-11')));
        $this->assertFalse($s->hasOverlap($motor, Carbon::parse('2030-02-13'), Carbon::parse('2030-02-15')));
        $this->assertFalse($s->hasOverlap($motor, Carbon::parse('2030-02-08'), Carbon::parse('2030-02-10')));
    }

    public function test_double_booking_is_rejected(): void
    {
        $motor = Motorcycle::factory()->create();
        $method = $this->method();
        $this->service()->create(User::factory()->create(), $motor, $method, $this->bookingData('2030-03-01', '2030-03-04'));

        $this->expectException(ValidationException::class);
        $this->service()->create(User::factory()->create(), $motor, $method, $this->bookingData('2030-03-02', '2030-03-03'));
    }

    public function test_cancelled_booking_releases_the_dates(): void
    {
        $motor = Motorcycle::factory()->create();
        $method = $this->method();
        $rental = $this->service()->create(User::factory()->create(), $motor, $method, $this->bookingData('2030-04-01', '2030-04-03'));
        $rental->update(['status' => 'cancelled']);

        $again = $this->service()->create(User::factory()->create(), $motor, $method, $this->bookingData('2030-04-01', '2030-04-03'));

        $this->assertInstanceOf(Rental::class, $again);
    }

    public function test_motor_in_maintenance_cannot_be_booked(): void
    {
        $motor = Motorcycle::factory()->create(['status' => 'maintenance']);

        $this->expectException(ValidationException::class);
        $this->service()->create(User::factory()->create(), $motor, $this->method(), $this->bookingData('2030-05-01', '2030-05-02'));
    }

    public function test_invoice_number_format(): void
    {
        $motor = Motorcycle::factory()->create();
        $rental = $this->service()->create(User::factory()->create(), $motor, $this->method(), $this->bookingData('2030-06-01', '2030-06-02'));

        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{3}$/', $rental->invoice_no);
        $this->assertSame('waiting_payment', $rental->status);
    }
}
