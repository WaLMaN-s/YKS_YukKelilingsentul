<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\TourBooking;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Menyajikan berkas privat (KTP, SIM, bukti bayar) hanya untuk pemilik dan admin yang berhak. */
class FileController extends Controller
{
    public function document(Customer $customer, string $type): StreamedResponse
    {
        Gate::authorize('viewDocuments', $customer);
        abort_unless(in_array($type, ['ktp', 'sim'], true), 404);

        $path = $type === 'ktp' ? $customer->ktp_path : $customer->sim_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function proof(Payment $payment): StreamedResponse
    {
        Gate::authorize('view', $payment->rental);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    public function tourProof(TourBooking $booking): StreamedResponse
    {
        Gate::authorize('view', $booking);
        abort_unless($booking->proof_path && Storage::disk('local')->exists($booking->proof_path), 404);

        return Storage::disk('local')->response($booking->proof_path);
    }
}
