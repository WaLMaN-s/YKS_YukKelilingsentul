<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\PaymentMethod;
use App\Models\TourBooking;
use App\Models\TourPackage;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\TourBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TourBookingController extends Controller
{
    public function __construct(private TourBookingService $tours, private BookingService $booking) {}

    public function create(Request $request, string $slug)
    {
        $package = TourPackage::where('is_active', true)->where('slug', $slug)->firstOrFail();

        return view('tours.book', [
            'package' => $package,
            'schedules' => $package->schedules()->where('date', '>=', today())->get()->filter(fn ($s) => $s->remaining > 0)->values(),
            'addons' => Addon::for('tour')->get(),
            'methods' => PaymentMethod::where('is_active', true)->orderBy('id')->get(),
            'user' => $request->user(),
            'preferred' => $request->query('jadwal'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tour_package_id' => ['required', Rule::exists('tour_packages', 'id')->where('is_active', true)],
            'trip_type' => ['required', Rule::in(['open', 'private'])],
            'tour_schedule_id' => ['nullable', 'required_if:trip_type,open', 'exists:tour_schedules,id'],
            'trip_date' => ['nullable', 'required_if:trip_type,private', 'date', 'after:today'],
            'people' => ['required', 'integer', 'between:1,40'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'exists:addons,id'],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'agree' => ['accepted'],
        ], [
            'trip_date.after' => 'Tanggal private trip minimal besok.',
            'agree.accepted' => 'Anda harus menyetujui kebijakan pemesanan.',
        ]);

        $package = TourPackage::findOrFail($data['tour_package_id']);
        $method = PaymentMethod::findOrFail($data['payment_method_id']);
        $booking = $this->tours->create($request->user(), $package, $method, $data);

        return redirect()->route('tour-booking.show', $booking->id)
            ->with('success', 'Booking wisata dibuat. Selesaikan pembayaran (lunas) agar jadwal terkonfirmasi.');
    }

    public function show(TourBooking $booking)
    {
        Gate::authorize('manage', $booking);
        $booking->load(['package', 'schedule', 'method']);

        return view('tours.booking', [
            'booking' => $booking,
            'canReschedule' => $this->tours->canReschedule($booking),
            'rescheduleDays' => $this->booking->rescheduleDays(),
            'schedules' => $booking->trip_type === 'open'
                ? $booking->package->schedules()->where('date', '>', today())->where('id', '!=', $booking->tour_schedule_id)->get()->filter(fn ($s) => $s->remaining >= $booking->people)->values()
                : collect(),
        ]);
    }

    public function uploadProof(Request $request, TourBooking $booking, NotificationService $notifier): RedirectResponse
    {
        Gate::authorize('manage', $booking);
        abort_unless($booking->canUploadProof(), 403, 'Bukti pembayaran tidak dapat diunggah pada status ini.');

        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096']]);

        if ($booking->proof_path) {
            Storage::disk('local')->delete($booking->proof_path);
        }
        $booking->update([
            'proof_path' => $request->file('proof')->store('payment-proofs', 'local'),
            'status' => 'waiting_verification',
            'payment_note' => null,
        ]);
        $notifier->toAdmins(['tour', 'payment'], 'Pembayaran wisata baru', 'Bukti pembayaran #'.$booking->invoice_no.' menunggu verifikasi.', route('admin.booking-wisata.show', $booking->id));

        return back()->with('success', 'Bukti pembayaran terkirim. Admin akan memverifikasi secepatnya.');
    }

    public function reschedule(Request $request, TourBooking $booking): RedirectResponse
    {
        Gate::authorize('manage', $booking);
        $request->validate([
            'tour_schedule_id' => ['nullable', 'exists:tour_schedules,id'],
            'trip_date' => ['nullable', 'date'],
        ]);
        $this->tours->reschedule($booking, $request->only(['tour_schedule_id', 'trip_date']));

        return back()->with('success', 'Jadwal berhasil diubah.');
    }

    public function cancel(TourBooking $booking): RedirectResponse
    {
        Gate::authorize('manage', $booking);
        $forfeit = $this->tours->cancelByCustomer($booking);

        return redirect()->route('dashboard.tours')->with($forfeit ? 'error' : 'success', $forfeit
            ? 'Booking dibatalkan pada hari H. Sesuai kebijakan, pembayaran hangus.'
            : 'Booking dibatalkan. Jika sudah membayar, admin akan menghubungi Anda untuk refund.');
    }
}
