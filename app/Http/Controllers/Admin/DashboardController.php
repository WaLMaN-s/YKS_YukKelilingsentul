<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Motorcycle;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\TourBooking;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'trail' => (int) Motorcycle::ofType('trail')->sum('stock'),
            'jeep' => (int) Motorcycle::ofType('jeep')->sum('stock'),
            'rented' => (int) RentalItem::whereHas('rental', fn ($q) => $q->where('status', 'rented'))->sum('quantity'),
            'tour_pending' => TourBooking::where('status', 'waiting_verification')->count(),
            'customers' => User::customers()->count(),
            'today' => Rental::whereDate('created_at', today())->count(),
            'pending' => Rental::whereIn('status', ['pending', 'waiting_payment'])->count(),
            'revenue' => (int) Payment::where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount')
                + (int) TourBooking::whereNotNull('paid_at')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
        ];

        // 7 hari terakhir
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $payments = Payment::where('status', 'paid')->where('paid_at', '>=', $days->first())->get(['amount', 'paid_at'])
            ->concat(TourBooking::whereNotNull('paid_at')->where('status', '!=', 'cancelled')->where('paid_at', '>=', $days->first())->get(['total', 'paid_at'])->map(fn ($t) => (object) ['amount' => $t->total, 'paid_at' => $t->paid_at]))
            ->groupBy(fn ($p) => $p->paid_at->toDateString());
        $rentalsByDay = Rental::where('created_at', '>=', $days->first())->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->toDateString());

        $week = [
            'labels' => $days->map(fn ($d) => $d->translatedFormat('j M'))->all(),
            'revenue' => $days->map(fn ($d) => (int) $payments->get($d->toDateString(), collect())->sum('amount'))->all(),
            'rentals' => $days->map(fn ($d) => $rentalsByDay->get($d->toDateString(), collect())->count())->all(),
        ];

        // 6 bulan terakhir
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i));
        $monthPayments = Payment::where('status', 'paid')->where('paid_at', '>=', $months->first())->get(['amount', 'paid_at'])
            ->concat(TourBooking::whereNotNull('paid_at')->where('status', '!=', 'cancelled')->where('paid_at', '>=', $months->first())->get(['total', 'paid_at'])->map(fn ($t) => (object) ['amount' => $t->total, 'paid_at' => $t->paid_at]))
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'));
        $monthly = [
            'labels' => $months->map(fn ($m) => $m->translatedFormat('M Y'))->all(),
            'revenue' => $months->map(fn ($m) => (int) $monthPayments->get($m->format('Y-m'), collect())->sum('amount'))->all(),
        ];

        $topMotors = RentalItem::select('motorcycle_id', DB::raw('count(*) as total'))
            ->groupBy('motorcycle_id')->orderByDesc('total')->with('motorcycle.images')->limit(5)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'week' => $week,
            'monthly' => $monthly,
            'topMotors' => $topMotors,
            'recent' => Rental::with('item.motorcycle')->latest()->take(6)->get(),
            'activities' => auth()->user()->appNotifications()->take(6)->get(),
            'tourCount' => TourPackage::where('is_active', true)->count(),
        ]);
    }
}
