<?php

namespace Database\Seeders;

use App\Models\Motorcycle;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Promo;
use App\Models\Rental;
use App\Models\RentalPackage;
use App\Models\RentalReturn;
use App\Models\TourBooking;
use App\Models\TourPackage;
use App\Models\Review;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class RentalSeeder extends Seeder
{
    public function run(): void
    {
        if (Rental::exists()) {
            return;
        }

        $users = User::customers()->orderBy('id')->get();
        $motors = Motorcycle::orderBy('id')->get();
        $bca = PaymentMethod::where('name', 'BCA')->first();
        $qris = PaymentMethod::where('name', 'QRIS')->first();
        $cash = PaymentMethod::where('name', 'Cash')->first();
        $promo = Promo::where('code', 'HEMAT20')->first();

        // [user idx, motor idx, mulai (offset hari), durasi, status, metode, status bayar, promo?]
        $rows = [
            [0, 0, -20, 2, 'completed', $bca, 'paid', true],
            [1, 1, -14, 1, 'completed', $qris, 'paid', false],
            [2, 2, -9, 3, 'completed', $bca, 'paid', false],
            [3, 3, -6, 1, 'completed', $cash, 'paid', false],
            [4, 0, -3, 2, 'rented', $bca, 'paid', false],
            [0, 4, -1, 3, 'rented', $qris, 'paid', false],
            [1, 5, 2, 2, 'confirmed', $bca, 'paid', false],
            [2, 1, 4, 1, 'paid', $bca, 'paid', false],
            [3, 2, 6, 2, 'waiting_payment', $bca, 'pending', false],
            [4, 3, 8, 1, 'pending', $cash, 'pending', false],
            [0, 6, -12, 1, 'cancelled', $bca, 'pending', false],
        ];

        $n = 0;
        foreach ($rows as [$u, $m, $offset, $days, $status, $method, $payStatus, $withPromo]) {
            $user = $users[$u];
            $motor = $motors[$m];
            $start = now()->addDays($offset)->startOfDay();
            $end = $start->copy()->addDays($days);
            $subtotal = $motor->price_daily * $days;
            $discount = ($withPromo && $promo) ? min((int) floor($subtotal * 0.2), 100000) : 0;
            $deposit = $motor->deposit;
            $created = $start->copy()->subDays(2)->setTime(10, 0);

            $rental = Rental::create([
                'invoice_no' => 'INV-'.$created->format('Ymd').'-'.str_pad((string) (++$n), 3, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'location_id' => 1,
                'promo_id' => $discount ? $promo->id : null,
                'renter_name' => $user->name, 'renter_phone' => $user->phone, 'renter_email' => $user->email,
                'start_date' => $start, 'end_date' => $end, 'days' => $days,
                'subtotal' => $subtotal, 'delivery_fee' => 0, 'deposit' => $deposit, 'discount' => $discount,
                'total' => $subtotal + $deposit - $discount,
                'status' => $status,
                'confirmed_at' => in_array($status, ['confirmed', 'rented', 'completed']) ? $created->copy()->addHours(3) : null,
                'picked_up_at' => in_array($status, ['rented', 'completed']) ? $start->copy()->setTime(8, 30) : null,
                'returned_at' => $status === 'completed' ? $end->copy()->setTime(16, 0) : null,
                'completed_at' => $status === 'completed' ? $end->copy()->setTime(16, 30) : null,
                'cancelled_reason' => $status === 'cancelled' ? 'Customer berhalangan hadir' : null,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            $rental->items()->create(['motorcycle_id' => $motor->id, 'price_per_day' => $motor->price_daily, 'days' => $days, 'subtotal' => $subtotal]);

            Payment::create([
                'rental_id' => $rental->id, 'payment_method_id' => $method->id, 'amount' => $rental->total,
                'status' => $payStatus, 'paid_at' => $payStatus === 'paid' ? $created->copy()->addHours(2) : null,
                'verified_at' => $payStatus === 'paid' ? $created->copy()->addHours(2) : null,
                'created_at' => $created, 'updated_at' => $created,
            ]);

            if ($discount) {
                $rental->promo->usages()->create(['user_id' => $user->id, 'rental_id' => $rental->id, 'discount' => $discount]);
                $promo->increment('used');
            }

            if ($status === 'completed') {
                $late = $n === 3 ? 2 : 0; // satu rental terlambat 2 jam
                RentalReturn::create([
                    'rental_id' => $rental->id, 'returned_at' => $end->copy()->setTime(17 + $late, 0), 'condition' => 'baik',
                    'fuel_level' => 'full', 'late_hours' => $late, 'late_fee' => $late * 25000,
                    'damage_fee' => 0, 'notes' => 'Motor kembali dalam kondisi baik.',
                ]);
                if ($late) {
                    $rental->update(['extra_fee' => $late * 25000]);
                }
                Review::create([
                    'rental_id' => $rental->id, 'user_id' => $user->id, 'motorcycle_id' => $motor->id,
                    'rating' => [5, 5, 4, 5][$n - 1] ?? 5,
                    'comment' => [
                        'Motor nya mantap, jalur nya keren banget! Next pasti kesini lagi.',
                        'Pelayanannya ramah, guide nya berpengalaman. Sangat recommended!',
                        'Sewa motor disini gampang dan terpercaya. Terima kasih YKS!',
                        'Motor terawat, proses cepat. Tapi sempat telat sedikit kembalinya.',
                    ][$n - 1] ?? 'Mantap!',
                    'status' => $n === 4 ? 'pending' : 'approved',
                ]);
            }
        }

        // Contoh jeep (paket + add-on), pesanan manual WA, dan booking wisata (open & private trip).
        $jeep = Motorcycle::ofType('jeep')->orderBy('id')->first();
        $package = RentalPackage::where('name', 'Jeep + Trekking')->first();
        $jeepStart = now()->addDays(10)->startOfDay();
        $jeepRental = Rental::create([
            'invoice_no' => 'INV-'.now()->format('Ymd').'-901', 'user_id' => $users[1]->id, 'package_id' => $package->id,
            'renter_name' => $users[1]->name, 'renter_phone' => $users[1]->phone, 'renter_email' => $users[1]->email,
            'start_date' => $jeepStart, 'end_date' => $jeepStart->copy()->addDay(), 'days' => 1, 'quantity' => 1, 'people' => 4,
            'subtotal' => $package->price, 'addons' => [['id' => 1, 'name' => 'Sepatu', 'price' => 100000, 'per' => 'person', 'subtotal' => 400000]], 'addons_total' => 400000,
            'deposit' => 0, 'total' => $package->price + 400000, 'status' => 'confirmed', 'confirmed_at' => now(), 'source' => 'website',
        ]);
        $jeepRental->items()->create(['motorcycle_id' => $jeep->id, 'quantity' => 1, 'price_per_day' => $package->price, 'days' => 1, 'subtotal' => $package->price]);
        Payment::create(['rental_id' => $jeepRental->id, 'payment_method_id' => $bca->id, 'amount' => $jeepRental->total, 'status' => 'paid', 'paid_at' => now(), 'verified_at' => now()]);

        $manualStart = now()->addDays(12)->startOfDay();
        $manualUser = $users[3];
        $manual = Rental::create([
            'invoice_no' => 'INV-'.now()->format('Ymd').'-902', 'user_id' => $manualUser->id,
            'renter_name' => $manualUser->name, 'renter_phone' => $manualUser->phone, 'renter_email' => $manualUser->email,
            'start_date' => $manualStart, 'end_date' => $manualStart->copy()->addDays(2), 'days' => 2, 'quantity' => 3, 'people' => 3,
            'subtotal' => $motors[0]->price_daily * 2 * 3, 'deposit' => $motors[0]->deposit * 3, 'total' => ($motors[0]->price_daily * 2 + $motors[0]->deposit) * 3,
            'status' => 'confirmed', 'confirmed_at' => now(), 'source' => 'whatsapp', 'notes' => 'Rombongan 3 orang, dipesan via WhatsApp.',
        ]);
        $manual->items()->create(['motorcycle_id' => $motors[0]->id, 'quantity' => 3, 'price_per_day' => $motors[0]->price_daily, 'days' => 2, 'subtotal' => $manual->subtotal]);
        $dp = (int) round($manual->total / 2);
        Payment::create(['rental_id' => $manual->id, 'payment_method_id' => $bca->id, 'amount' => $dp, 'status' => 'paid', 'paid_at' => now(), 'verified_at' => now(), 'note' => 'DP 50%']);
        Payment::create(['rental_id' => $manual->id, 'payment_method_id' => $bca->id, 'amount' => $manual->total - $dp, 'status' => 'pending', 'note' => 'Pelunasan']);

        $tour = TourPackage::where('slug', 'paket-keliling-gunung-pancar')->first();
        $schedule = $tour?->schedules()->orderBy('date')->first();
        if ($tour && $schedule) {
            $schedule->increment('booked', 2);
            TourBooking::create([
                'invoice_no' => 'TRP-'.now()->format('Ymd').'-001', 'user_id' => $users[0]->id, 'tour_package_id' => $tour->id, 'tour_schedule_id' => $schedule->id,
                'trip_type' => 'open', 'trip_date' => $schedule->date, 'people' => 2, 'price_per_person' => $tour->price, 'subtotal' => $tour->price * 2,
                'total' => $tour->price * 2, 'contact_name' => $users[0]->name, 'contact_phone' => $users[0]->phone,
                'status' => 'confirmed', 'payment_method_id' => $bca->id, 'paid_at' => now(), 'source' => 'website',
            ]);
            TourBooking::create([
                'invoice_no' => 'TRP-'.now()->format('Ymd').'-002', 'user_id' => $users[2]->id, 'tour_package_id' => $tour->id,
                'trip_type' => 'private', 'trip_date' => now()->addDays(5)->startOfDay(), 'people' => 4, 'price_per_person' => $tour->price, 'subtotal' => $tour->price * 4,
                'addons' => [['id' => 2, 'name' => 'Guide', 'price' => 200000, 'per' => 'booking', 'subtotal' => 200000]], 'addons_total' => 200000,
                'total' => $tour->price * 4 + 200000, 'contact_name' => $users[2]->name, 'contact_phone' => $users[2]->phone,
                'status' => 'waiting_payment', 'payment_method_id' => $bca->id, 'source' => 'website',
            ]);
        }

        // Notifikasi contoh untuk admin & customer demo.
        $admin = User::where('email', 'admin@rentalmotor.test')->first();
        $customer = User::where('email', 'customer@rentalmotor.test')->first();
        UserNotification::create(['user_id' => $admin->id, 'title' => 'Booking baru', 'body' => 'Ada booking baru yang menunggu pembayaran.', 'url' => route('admin.penyewaan.index'), 'created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2)]);
        UserNotification::create(['user_id' => $admin->id, 'title' => 'Review baru', 'body' => 'Ada review yang menunggu persetujuan.', 'url' => route('admin.review.index'), 'created_at' => now()->subHours(5), 'updated_at' => now()->subHours(5)]);
        UserNotification::create(['user_id' => $customer->id, 'title' => 'Motor sedang disewa', 'body' => 'Selamat berpetualang! Jangan lupa kembalikan motor tepat waktu.', 'url' => route('dashboard.bookings'), 'created_at' => now()->subHours(1), 'updated_at' => now()->subHours(1)]);
    }
}
