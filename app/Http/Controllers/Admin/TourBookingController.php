<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourBooking;
use App\Services\NotificationService;
use App\Services\TourBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TourBookingController extends Controller
{
    public function __construct(private NotificationService $notifier, private TourBookingService $tours) {}

    public function index(Request $request)
    {
        $bookings = TourBooking::with(['package', 'method'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('jenis'), fn ($q) => $q->where('trip_type', $request->jenis))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_no', 'like', '%'.$request->q.'%')->orWhere('contact_name', 'like', '%'.$request->q.'%')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.tour-bookings.index', ['bookings' => $bookings]);
    }

    public function show(TourBooking $booking)
    {
        return view('admin.tour-bookings.show', ['booking' => $booking->load(['package', 'schedule', 'method', 'user', 'verifier'])]);
    }

    public function verify(TourBooking $booking): RedirectResponse
    {
        if (! in_array($booking->status, ['waiting_payment', 'waiting_verification'], true)) {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }
        $booking->update(['status' => 'confirmed', 'paid_at' => now(), 'verified_by' => auth()->id(), 'payment_note' => null]);
        $this->notifier->toUser($booking->user_id, 'Pembayaran diterima', 'Booking wisata #'.$booking->invoice_no.' lunas dan terkonfirmasi.', route('tour-booking.show', $booking->id));

        return back()->with('success', 'Pembayaran diverifikasi, booking dikonfirmasi.');
    }

    public function reject(Request $request, TourBooking $booking): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:300']], ['note.required' => 'Tulis alasan penolakan.']);
        if (! in_array($booking->status, ['waiting_payment', 'waiting_verification'], true)) {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }
        $booking->update(['status' => 'waiting_payment', 'payment_note' => $data['note']]);
        $this->notifier->toUser($booking->user_id, 'Pembayaran ditolak', 'Bukti pembayaran #'.$booking->invoice_no.' ditolak: '.$data['note'], route('tour-booking.show', $booking->id));

        return back()->with('success', 'Pembayaran ditolak dan customer diberi tahu.');
    }

    public function complete(TourBooking $booking): RedirectResponse
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Hanya booking terkonfirmasi yang bisa diselesaikan.');
        }
        $booking->update(['status' => 'completed']);
        $this->notifier->toUser($booking->user_id, 'Trip selesai', 'Terima kasih sudah ikut trip bersama kami!', route('tour-booking.show', $booking->id));

        return back()->with('success', 'Trip ditandai selesai.');
    }

    public function cancel(Request $request, TourBooking $booking): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);
        if (! $booking->isOpen()) {
            return back()->with('error', 'Booking ini sudah selesai atau dibatalkan.');
        }
        $this->tours->cancel($booking, $data['reason'] ?? 'Dibatalkan oleh admin');
        $this->notifier->toUser($booking->user_id, 'Booking wisata dibatalkan', 'Booking #'.$booking->invoice_no.' dibatalkan. '.($data['reason'] ?? ''), route('tour-booking.show', $booking->id));

        return back()->with('success', 'Booking dibatalkan.');
    }
}
