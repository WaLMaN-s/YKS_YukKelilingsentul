<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Motorcycle;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\RentalPackage;
use App\Models\Role;
use App\Models\Rental;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Mencatat pesanan yang masuk lewat WhatsApp / Instagram / datang langsung ke dalam sistem. */
class ManualBookingController extends Controller
{
    public function __construct(private BookingService $booking) {}

    public function create()
    {
        return view('admin.rentals.manual', [
            'vehicles' => Motorcycle::where('status', 'available')->orderBy('vehicle_type')->orderBy('name')->get(),
            'packages' => RentalPackage::active()->where('vehicle_type', 'jeep')->get(),
            'addons' => Addon::for('rental')->get(),
            'methods' => PaymentMethod::where('is_active', true)->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'dpPercent' => (int) setting('dp_percent', 50),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $motor = Motorcycle::find($request->input('motor_id'));
        if ($motor?->isJeep() && $request->filled('start_date')) {
            try {
                $request->merge(['end_date' => Carbon::parse($request->input('start_date'))->addDay()->toDateString()]);
            } catch (\Throwable) {
                // validasi tanggal di bawah akan menolak input yang tidak valid
            }
        }

        $data = $request->validate([
            'motor_id' => ['required', 'exists:motorcycles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'quantity' => ['required', 'integer', 'between:1,'.BookingService::MAX_QTY],
            'package_id' => ['nullable', 'exists:rental_packages,id'],
            'people' => ['nullable', 'integer', 'between:1,40'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'exists:addons,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'promo_code' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'source' => ['required', Rule::in(['whatsapp', 'instagram', 'walk_in'])],
            'payment_mode' => ['required', Rule::in(['unpaid', 'dp', 'full'])],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['customer_name' => 'Nama customer', 'customer_phone' => 'Nomor HP', 'customer_email' => 'Email', 'source' => 'Sumber pesanan', 'payment_mode' => 'Status pembayaran']);

        $user = $this->findOrCreateCustomer($data);
        $method = PaymentMethod::findOrFail($data['payment_method_id']);

        $rental = $this->booking->create($user, Motorcycle::findOrFail($data['motor_id']), $method, $data + [
            'renter_name' => $data['customer_name'],
            'renter_phone' => $data['customer_phone'],
            'renter_email' => $user->email,
        ]);

        $message = 'Pesanan manual dicatat.';
        $payment = $rental->payment;
        if ($data['payment_mode'] === 'full') {
            $payment->update(['status' => 'paid', 'paid_at' => now(), 'verified_by' => auth()->id(), 'verified_at' => now(), 'note' => 'Dicatat manual — lunas']);
            $rental->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            $message .= ' Status: lunas & dikonfirmasi.';
        } elseif ($data['payment_mode'] === 'dp') {
            $percent = min(99, max(1, (int) setting('dp_percent', 50)));
            $dp = (int) round($rental->total * $percent / 100);
            $payment->update(['amount' => $dp, 'status' => 'paid', 'paid_at' => now(), 'verified_by' => auth()->id(), 'verified_at' => now(), 'note' => "DP {$percent}%"]);
            Payment::create(['rental_id' => $rental->id, 'payment_method_id' => $method->id, 'amount' => $rental->total - $dp, 'status' => 'pending', 'note' => 'Pelunasan']);
            $rental->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            $message .= " DP {$percent}% (".rupiah($dp).') tercatat, sisa '.rupiah($rental->total - $dp).' menunggu pelunasan.';
        }

        return redirect()->route('admin.penyewaan.show', $rental)->with('success', $message);
    }

    private function findOrCreateCustomer(array $data): User
    {
        $existing = null;
        if (filled($data['customer_email'] ?? null)) {
            $existing = User::where('email', $data['customer_email'])->first();
        }
        $existing ??= User::customers()->where('phone', $data['customer_phone'])->first();
        if ($existing) {
            return $existing;
        }

        $digits = preg_replace('/\D+/', '', $data['customer_phone']);
        $email = $data['customer_email'] ?? "tamu-{$digits}@manual.local";
        if (User::where('email', $email)->exists()) {
            $email = "tamu-{$digits}-".Str::lower(Str::random(4)).'@manual.local';
        }

        $user = User::create([
            'name' => $data['customer_name'],
            'email' => $email,
            'phone' => $data['customer_phone'],
            'password' => Str::random(32),
            'role_id' => Role::where('slug', 'customer')->value('id'),
        ]);
        Customer::create(['user_id' => $user->id]);

        return $user;
    }
}
