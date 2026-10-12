<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Review;
use App\Services\BookingService;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        $rentals = $user->rentals();

        return view('dashboard.index', [
            'stats' => [
                'total' => (clone $rentals)->count(),
                'active' => (clone $rentals)->whereIn('status', Rental::ACTIVE)->count(),
                'completed' => (clone $rentals)->where('status', 'completed')->count(),
                'spent' => (int) Payment::where('status', 'paid')->whereHas('rental', fn ($q) => $q->where('user_id', $user->id))->sum('amount'),
            ],
            'recent' => $user->rentals()->with('item.motorcycle.images', 'payment')->latest()->take(4)->get(),
        ]);
    }

    public function bookings(Request $request)
    {
        return view('dashboard.bookings', [
            'rentals' => $request->user()->rentals()->with('item.motorcycle.images', 'payment')->latest()->paginate(8),
        ]);
    }

    public function showBooking(Rental $rental, BookingService $booking)
    {
        Gate::authorize('manage', $rental);
        $rental->load(['item.motorcycle.images', 'payment.method', 'payments', 'location', 'promo', 'package', 'returnRecord', 'review']);

        return view('dashboard.booking-show', [
            'rental' => $rental,
            'canReschedule' => $booking->canReschedule($rental),
            'rescheduleDays' => $booking->rescheduleDays(),
        ]);
    }

    public function rescheduleBooking(Request $request, Rental $rental, BookingService $booking): RedirectResponse
    {
        Gate::authorize('manage', $rental);
        $data = $request->validate(['start_date' => ['required', 'date']]);
        $booking->reschedule($rental, Carbon::parse($data['start_date']));

        return back()->with('success', 'Jadwal berhasil diubah. Durasi dan harga tetap sama.');
    }

    public function cancelBooking(Rental $rental, BookingService $booking): RedirectResponse
    {
        Gate::authorize('manage', $rental);
        $forfeit = $booking->cancelByCustomer($rental);

        return redirect()->route('dashboard.bookings')->with($forfeit ? 'error' : 'success', $forfeit
            ? 'Booking dibatalkan pada hari H. Sesuai kebijakan, pembayaran hangus.'
            : 'Booking dibatalkan. Jika sudah membayar, admin akan menghubungi Anda untuk refund.');
    }

    public function tours(Request $request)
    {
        return view('dashboard.tours', [
            'bookings' => $request->user()->tourBookings()->with('package')->latest()->paginate(8),
        ]);
    }

    public function payments(Request $request)
    {
        return view('dashboard.payments', [
            'payments' => Payment::with(['rental.item.motorcycle', 'method'])
                ->whereHas('rental', fn ($q) => $q->where('user_id', $request->user()->id))
                ->latest()->paginate(10),
        ]);
    }

    public function reviews(Request $request)
    {
        $user = $request->user();

        return view('dashboard.reviews', [
            'pending' => $user->rentals()->where('status', 'completed')->doesntHave('review')->with('item.motorcycle')->latest()->get(),
            'reviews' => $user->reviews()->with('motorcycle')->latest()->get(),
        ]);
    }

    public function storeReview(Request $request, Rental $rental, NotificationService $notifier): RedirectResponse
    {
        Gate::authorize('manage', $rental);
        abort_unless($rental->status === 'completed' && ! $rental->review()->exists(), 403, 'Review hanya untuk rental selesai yang belum direview.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create($data + [
            'rental_id' => $rental->id,
            'user_id' => $request->user()->id,
            'motorcycle_id' => $rental->item->motorcycle_id,
            'status' => 'pending',
        ]);
        $notifier->toAdmins(['review'], 'Review baru', $rental->renter_name.' memberi review untuk #'.$rental->invoice_no.'.', route('admin.review.index', ['status' => 'pending']));

        return back()->with('success', 'Terima kasih! Review Anda akan tampil setelah disetujui admin.');
    }

    public function profile(Request $request)
    {
        return view('dashboard.profile', ['user' => $request->user()->load('customer')]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'nik' => ['nullable', 'digits:16'],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'sim_number' => ['nullable', 'string', 'max:30'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:3072'],
            'sim' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:3072'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']]);
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }
        $user->save();

        $profile = collect($data)->only(['nik', 'address', 'birth_date', 'sim_number'])->all();
        foreach (['ktp', 'sim'] as $doc) {
            if ($request->hasFile($doc)) {
                if ($customer?->{$doc.'_path'}) {
                    Storage::disk('local')->delete($customer->{$doc.'_path'});
                }
                $profile[$doc.'_path'] = $request->file($doc)->store('documents/'.$user->id, 'local');
            }
        }
        $user->customer()->updateOrCreate(['user_id' => $user->id], $profile);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
