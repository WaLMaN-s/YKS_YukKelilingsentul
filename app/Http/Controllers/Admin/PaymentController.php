<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $payments = Payment::with(['rental.item.motorcycle', 'method'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('rental', fn ($r) => $r
                ->where('invoice_no', 'like', '%'.$request->q.'%')->orWhere('renter_name', 'like', '%'.$request->q.'%')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.payments', ['payments' => $payments]);
    }

    public function verify(Payment $payment): RedirectResponse
    {
        if (! in_array($payment->status, ['pending', 'waiting_verification'], true)) {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }
        $payment->update(['status' => 'paid', 'paid_at' => now(), 'verified_by' => auth()->id(), 'verified_at' => now(), 'note' => null]);

        $rental = $payment->rental;
        if (in_array($rental->status, ['pending', 'waiting_payment'], true)) {
            $rental->update(['status' => 'paid']);
        }
        $this->notifier->toUser($rental->user_id, 'Pembayaran diterima', 'Pembayaran #'.$rental->invoice_no.' sebesar '.rupiah($payment->amount).' sudah diverifikasi.', route('dashboard.bookings.show', $rental->id));

        return back()->with('success', 'Pembayaran diverifikasi.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:300']], ['note.required' => 'Tulis alasan penolakan agar customer tahu apa yang harus diperbaiki.']);
        if (! in_array($payment->status, ['pending', 'waiting_verification'], true)) {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }
        $payment->update(['status' => 'rejected', 'note' => $data['note'], 'verified_by' => auth()->id(), 'verified_at' => now()]);

        $rental = $payment->rental;
        $this->notifier->toUser($rental->user_id, 'Pembayaran ditolak', 'Bukti pembayaran #'.$rental->invoice_no.' ditolak: '.$data['note'].' Silakan unggah ulang.', route('payment.show', $rental->id));

        return back()->with('success', 'Pembayaran ditolak dan customer diberi tahu.');
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:300']]);
        if ($payment->status !== 'paid') {
            return back()->with('error', 'Hanya pembayaran berstatus Dibayar yang bisa direfund.');
        }
        $payment->update(['status' => 'refund', 'note' => $data['note'] ?? 'Refund oleh admin']);

        $rental = $payment->rental;
        if (in_array($rental->status, ['paid', 'confirmed', 'pickup'], true)) {
            $rental->update(['status' => 'cancelled', 'cancelled_reason' => 'Refund pembayaran']);
        }
        $this->notifier->toUser($rental->user_id, 'Refund diproses', 'Pembayaran #'.$rental->invoice_no.' direfund sebesar '.rupiah($payment->amount).'.', route('dashboard.bookings.show', $rental->id));

        return back()->with('success', 'Pembayaran ditandai refund.');
    }
}
