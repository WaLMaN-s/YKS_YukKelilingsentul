<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Motorcycle;
use App\Models\PaymentMethod;
use App\Models\RentalPackage;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private BookingService $booking) {}

    public function create(Request $request)
    {
        $motor = Motorcycle::with('images')->where('slug', $request->query('motor'))->first();
        if (! $motor || $motor->status !== 'available') {
            return redirect()->route('motors.index')->with('error', 'Pilih kendaraan yang tersedia terlebih dahulu.');
        }

        $user = $request->user()->load('customer');

        return view('booking.create', [
            'motor' => $motor,
            'user' => $user,
            'customer' => $user->customer,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'methods' => PaymentMethod::where('is_active', true)->orderBy('id')->get(),
            'packages' => $motor->isJeep() ? RentalPackage::active()->where('vehicle_type', 'jeep')->get() : collect(),
            'addons' => Addon::for('rental')->get(),
            'selectedPackage' => $request->query('paket'),
            'start' => $request->query('start', today()->addDay()->toDateString()),
            'end' => $request->query('end', today()->addDays(2)->toDateString()),
            'selectedLocation' => $request->query('lokasi'),
            'minDays' => $this->booking->minDays(),
            'minAge' => (int) setting('min_age', 17),
            'maxQty' => min(BookingService::MAX_QTY, max(1, $motor->stock)),
        ]);
    }

    /** Hitung harga secara langsung (dipakai halaman booking). */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'motor_id' => ['required', 'exists:motorcycles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'promo_code' => ['nullable', 'string', 'max:40'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'package_id' => ['nullable', 'integer'],
            'people' => ['nullable', 'integer', 'min:1', 'max:100'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer'],
        ]);

        $motor = Motorcycle::findOrFail($data['motor_id']);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'] ?? $start->copy()->addDay())->startOfDay();

        if (! $motor->isJeep() && $end->lte($start)) {
            return response()->json(['available' => false, 'errors' => ['Tanggal kembali harus setelah tanggal mulai.']]);
        }

        $q = $this->booking->quote(
            $motor, $start, $end, $data['promo_code'] ?? null, $data['location_id'] ?? null,
            (int) ($data['quantity'] ?? 1), $data['package_id'] ?? null, array_map('intval', $data['addons'] ?? []),
            isset($data['people']) ? (int) $data['people'] : null,
        );
        unset($q['promo'], $q['package']);

        return response()->json($q);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->load('customer');
        $customer = $user->customer;
        $minAge = (int) setting('min_age', 17);

        // Jeep = perjalanan sehari: tanggal kembali otomatis H+1.
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
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'quantity' => ['required', 'integer', 'between:1,'.BookingService::MAX_QTY],
            'package_id' => ['nullable', 'exists:rental_packages,id'],
            'people' => ['nullable', 'integer', 'between:1,40'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'exists:addons,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'promo_code' => ['nullable', 'string', 'max:40'],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'renter_name' => ['required', 'string', 'max:100'],
            'renter_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'renter_email' => ['required', 'email', 'max:150'],
            'nik' => ['required', 'digits:16'],
            'address' => ['required', 'string', 'max:500'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears($minAge)->toDateString()],
            'sim_number' => ['required', 'string', 'max:30'],
            'ktp' => [$customer?->ktp_path ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'max:3072'],
            'sim' => [$customer?->sim_path ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'max:3072'],
            'notes' => ['nullable', 'string', 'max:500'],
            'agree' => ['accepted'],
        ], [
            'birth_date.before_or_equal' => 'Usia minimal penyewa adalah '.$minAge.' tahun.',
            'agree.accepted' => 'Anda harus menyetujui syarat sewa dan kebijakan pemesanan.',
        ]);

        $vehicle = Motorcycle::findOrFail($data['motor_id']);
        $method = PaymentMethod::findOrFail($data['payment_method_id']);

        // Booking dibuat lebih dulu (cek stok di dalam transaksi); berkas disimpan setelah sukses.
        $rental = $this->booking->create($user, $vehicle, $method, $data + ['source' => 'website']);

        $profile = [
            'nik' => $data['nik'],
            'address' => $data['address'],
            'birth_date' => $data['birth_date'],
            'sim_number' => $data['sim_number'],
        ];
        foreach (['ktp', 'sim'] as $doc) {
            if ($request->hasFile($doc)) {
                if ($customer?->{$doc.'_path'}) {
                    Storage::disk('local')->delete($customer->{$doc.'_path'});
                }
                $profile[$doc.'_path'] = $request->file($doc)->store('documents/'.$user->id, 'local');
            }
        }
        Customer::updateOrCreate(['user_id' => $user->id], $profile);
        if (blank($user->phone)) {
            $user->update(['phone' => $data['renter_phone']]);
        }

        return redirect()->route('payment.show', $rental->id)
            ->with('success', 'Booking berhasil dibuat. Selesaikan pembayaran (lunas) untuk melanjutkan.');
    }
}
