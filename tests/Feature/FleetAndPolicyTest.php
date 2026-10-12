<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Motorcycle;
use App\Models\PaymentMethod;
use App\Models\RentalPackage;
use App\Models\TourPackage;
use App\Models\TourSchedule;
use App\Models\User;
use App\Services\BookingService;
use App\Services\TourBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FleetAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function data(string $start, string $end, array $extra = []): array
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

    private function jeep(array $extra = []): Motorcycle
    {
        return Motorcycle::factory()->create($extra + ['vehicle_type' => 'jeep', 'stock' => 10, 'capacity' => 4, 'price_daily' => 1100000, 'deposit' => 0]);
    }

    private function jeepPackage(string $name = 'Jeep + Trekking', int $price = 1700000): RentalPackage
    {
        return RentalPackage::create(['vehicle_type' => 'jeep', 'name' => $name, 'price' => $price, 'is_active' => true]);
    }

    // ---------- stok unit ----------

    public function test_many_units_can_be_booked_until_stock_runs_out(): void
    {
        $motor = Motorcycle::factory()->create(['stock' => 3]);
        $method = $this->method();

        foreach (range(1, 3) as $i) {
            $this->service()->create(User::factory()->create(), $motor, $method, $this->data('2030-07-01', '2030-07-03'));
        }

        $this->assertSame(0, $this->service()->unitsLeft($motor, Carbon::parse('2030-07-01'), Carbon::parse('2030-07-03')));

        $this->expectException(ValidationException::class);
        $this->service()->create(User::factory()->create(), $motor, $method, $this->data('2030-07-02', '2030-07-04'));
    }

    public function test_quantity_consumes_several_units_at_once(): void
    {
        $motor = Motorcycle::factory()->create(['stock' => 5]);
        $method = $this->method();

        $this->service()->create(User::factory()->create(), $motor, $method, $this->data('2030-07-10', '2030-07-12', ['quantity' => 3]));
        $this->assertSame(2, $this->service()->unitsLeft($motor, Carbon::parse('2030-07-10'), Carbon::parse('2030-07-12')));

        $this->expectException(ValidationException::class);
        $this->service()->create(User::factory()->create(), $motor, $method, $this->data('2030-07-10', '2030-07-12', ['quantity' => 3]));
    }

    public function test_units_are_released_for_non_overlapping_days(): void
    {
        $motor = Motorcycle::factory()->create(['stock' => 1]);
        $this->service()->create(User::factory()->create(), $motor, $this->method(), $this->data('2030-07-20', '2030-07-22'));

        $this->assertSame(1, $this->service()->unitsLeft($motor, Carbon::parse('2030-07-22'), Carbon::parse('2030-07-24')));
        $this->assertContains('2030-07-21', $this->service()->fullDays($motor, 4000));
    }

    // ---------- jeep & paket ----------

    public function test_jeep_price_follows_package_and_trip_is_one_day(): void
    {
        $jeep = $this->jeep();
        $pkg = $this->jeepPackage();

        $q = $this->service()->quote($jeep, Carbon::parse('2030-08-10'), Carbon::parse('2030-08-20'), null, null, 2, $pkg->id, [], 8);

        $this->assertSame(1, $q['days']);
        $this->assertSame('2030-08-11', $q['end_date']);
        $this->assertSame(3400000, $q['subtotal']);
        $this->assertSame(0, $q['deposit']);
        $this->assertSame(3400000, $q['total']);
        $this->assertTrue($q['available']);
    }

    public function test_jeep_needs_a_package_and_respects_max_four_people_per_jeep(): void
    {
        $jeep = $this->jeep();
        $pkg = $this->jeepPackage();

        $this->assertFalse($this->service()->quote($jeep, Carbon::parse('2030-08-10'), Carbon::parse('2030-08-11'), null, null, 1, null)['available']);
        $this->assertFalse($this->service()->quote($jeep, Carbon::parse('2030-08-10'), Carbon::parse('2030-08-11'), null, null, 1, $pkg->id, [], 5)['available']);
        $this->assertTrue($this->service()->quote($jeep, Carbon::parse('2030-08-10'), Carbon::parse('2030-08-11'), null, null, 1, $pkg->id, [], 4)['available']);
    }

    public function test_jeep_booking_is_stored_with_package_and_people(): void
    {
        $jeep = $this->jeep();
        $pkg = $this->jeepPackage('Jeep Saja', 1100000);

        $rental = $this->service()->create(User::factory()->create(), $jeep, $this->method(), $this->data('2030-08-15', '2030-08-15', ['package_id' => $pkg->id, 'people' => 4]));

        $this->assertSame($pkg->id, $rental->package_id);
        $this->assertSame(4, $rental->people);
        $this->assertSame(1, $rental->days);
        $this->assertSame('2030-08-16', $rental->end_date->toDateString());
        $this->assertSame(1100000, $rental->total);
    }

    // ---------- add-on ----------

    public function test_addons_are_priced_per_person_or_per_booking(): void
    {
        $motor = Motorcycle::factory()->create(['stock' => 5, 'price_daily' => 250000, 'deposit' => 500000]);
        $shoes = Addon::create(['name' => 'Sepatu', 'price' => 100000, 'per' => 'person', 'applies_to' => 'both', 'is_active' => true]);
        $guide = Addon::create(['name' => 'Guide', 'price' => 200000, 'per' => 'booking', 'applies_to' => 'both', 'is_active' => true]);
        Addon::create(['name' => 'Nonaktif', 'price' => 999999, 'per' => 'booking', 'applies_to' => 'both', 'is_active' => false]);

        $q = $this->service()->quote($motor, Carbon::parse('2030-09-01'), Carbon::parse('2030-09-02'), null, null, 2, null, [$shoes->id, $guide->id]);

        $this->assertSame(500000, $q['subtotal']);
        $this->assertSame(400000, $q['addons_total']); // 100.000 × 2 pengendara + 200.000
        $this->assertCount(2, $q['addons']);
        $this->assertSame(500000 + 400000 + 1000000, $q['total']);
    }

    // ---------- kebijakan jadwal & pembatalan ----------

    public function test_reschedule_is_allowed_until_h_minus_2_only(): void
    {
        $motor = Motorcycle::factory()->create();
        $method = $this->method();

        $far = $this->service()->create(User::factory()->create(), $motor, $method, $this->data(now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString()));
        $this->assertTrue($this->service()->canReschedule($far));

        $moved = $this->service()->reschedule($far, now()->addDays(20));
        $this->assertSame(now()->addDays(20)->toDateString(), $moved->start_date->toDateString());
        $this->assertSame(now()->addDays(22)->toDateString(), $moved->end_date->toDateString());
        $this->assertSame(1, $moved->rescheduled_count);

        $motor2 = Motorcycle::factory()->create();
        $near = $this->service()->create(User::factory()->create(), $motor2, $method, $this->data(now()->addDay()->toDateString(), now()->addDays(3)->toDateString()));
        $this->assertFalse($this->service()->canReschedule($near));

        $this->expectException(ValidationException::class);
        $this->service()->reschedule($near, now()->addDays(10));
    }

    public function test_reschedule_cannot_move_into_a_fully_booked_period(): void
    {
        $motor = Motorcycle::factory()->create(['stock' => 1]);
        $method = $this->method();
        $this->service()->create(User::factory()->create(), $motor, $method, $this->data(now()->addDays(30)->toDateString(), now()->addDays(32)->toDateString()));
        $mine = $this->service()->create(User::factory()->create(), $motor, $method, $this->data(now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString()));

        $this->expectException(ValidationException::class);
        $this->service()->reschedule($mine, now()->addDays(31));
    }

    public function test_cancelling_on_day_h_forfeits_payment_but_earlier_does_not(): void
    {
        $method = $this->method();

        $today = $this->service()->create(User::factory()->create(), Motorcycle::factory()->create(), $method, $this->data(now()->toDateString(), now()->addDay()->toDateString()));
        $this->assertTrue($this->service()->cancelByCustomer($today));
        $this->assertTrue($today->fresh()->forfeited);
        $this->assertSame('cancelled', $today->fresh()->status);

        $later = $this->service()->create(User::factory()->create(), Motorcycle::factory()->create(), $method, $this->data(now()->addDays(5)->toDateString(), now()->addDays(6)->toDateString()));
        $this->assertFalse($this->service()->cancelByCustomer($later));
        $this->assertFalse($later->fresh()->forfeited);
    }

    // ---------- booking wisata ----------

    private function tour(int $quota = 4): array
    {
        $package = TourPackage::create(['name' => 'Paket Test', 'slug' => 'paket-test', 'price' => 350000, 'duration' => '1 Hari', 'quota' => 8, 'is_active' => true]);
        $schedule = TourSchedule::create(['tour_package_id' => $package->id, 'date' => now()->addDays(10), 'quota' => $quota, 'booked' => 0]);

        return [$package, $schedule];
    }

    private function tourData(array $extra = []): array
    {
        return $extra + ['contact_name' => 'Tester', 'contact_phone' => '081234567890', 'people' => 2];
    }

    public function test_private_trip_can_be_booked_on_any_future_date_without_a_schedule(): void
    {
        [$package] = $this->tour();
        $addon = Addon::create(['name' => 'Guide', 'price' => 200000, 'per' => 'booking', 'applies_to' => 'tour', 'is_active' => true]);

        $b = app(TourBookingService::class)->create(User::factory()->create(), $package, $this->method(), $this->tourData([
            'trip_type' => 'private', 'trip_date' => now()->addDays(3)->toDateString(), 'people' => 3, 'addons' => [$addon->id],
        ]));

        $this->assertSame('private', $b->trip_type);
        $this->assertNull($b->tour_schedule_id);
        $this->assertSame(350000 * 3 + 200000, $b->total);
        $this->assertSame('waiting_payment', $b->status);
    }

    public function test_open_trip_uses_quota_and_cancelling_releases_seats(): void
    {
        [$package, $schedule] = $this->tour(4);
        $svc = app(TourBookingService::class);
        $method = $this->method();

        $first = $svc->create(User::factory()->create(), $package, $method, $this->tourData(['trip_type' => 'open', 'tour_schedule_id' => $schedule->id, 'people' => 3]));
        $this->assertSame(3, $schedule->fresh()->booked);

        try {
            $svc->create(User::factory()->create(), $package, $method, $this->tourData(['trip_type' => 'open', 'tour_schedule_id' => $schedule->id, 'people' => 2]));
            $this->fail('Kuota seharusnya penuh.');
        } catch (ValidationException) {
            $this->assertSame(3, $schedule->fresh()->booked);
        }

        $svc->cancelByCustomer($first);
        $this->assertSame(0, $schedule->fresh()->booked);
    }

    public function test_tour_private_trip_cannot_be_today(): void
    {
        [$package] = $this->tour();

        $this->expectException(ValidationException::class);
        app(TourBookingService::class)->create(User::factory()->create(), $package, $this->method(), $this->tourData(['trip_type' => 'private', 'trip_date' => now()->toDateString()]));
    }
}
