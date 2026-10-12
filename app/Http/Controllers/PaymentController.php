<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function show(Rental $rental)
    {
        Gate::authorize('manage', $rental);
        $rental->load(['item.motorcycle.images', 'payment.method', 'location']);

        return view('booking.payment', ['rental' => $rental, 'payment' => $rental->payment]);
    }

    public function uploadProof(Request $request, Rental $rental, NotificationService $notifier): RedirectResponse
    {
        Gate::authorize('manage', $rental);
        $payment = $rental->payment;

        abort_unless($payment && $payment->canUploadProof() && ! in_array($rental->status, ['cancelled', 'completed'], true), 403, 'Bukti pembayaran tidak dapat diunggah pada status ini.');

        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096']]);

        if ($payment->proof_path) {
            Storage::disk('local')->delete($payment->proof_path);
        }
        $payment->update([
            'proof_path' => $request->file('proof')->store('payment-proofs', 'local'),
            'status' => 'waiting_verification',
            'note' => null,
        ]);

        $notifier->toAdmins(['payment'], 'Pembayaran baru', 'Bukti pembayaran #'.$rental->invoice_no.' dari '.$rental->renter_name.' menunggu verifikasi.', route('admin.pembayaran.index', ['status' => 'waiting_verification']));

        return back()->with('success', 'Bukti pembayaran terkirim. Admin akan memverifikasi secepatnya.');
    }
}
