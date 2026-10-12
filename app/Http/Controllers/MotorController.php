<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Motorcycle;
use App\Models\MotorCategory;
use App\Models\Rental;
use App\Models\Addon;
use App\Models\RentalPackage;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MotorController extends Controller
{
    public function __construct(private BookingService $booking) {}

    public function index(Request $request)
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after:start'],
        ]);

        $query = Motorcycle::visible()->with(['images', 'category'])
            ->withAvg(['reviews as avg_rating' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount('rentalItems');

        if (in_array($request->query('jenis'), ['trail', 'jeep'], true)) {
            $query->where('vehicle_type', $request->query('jenis'));
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('brand', 'like', "%{$q}%"));
        }
        if ($category = $request->query('kategori')) {
            $query->whereHas('category', fn ($w) => $w->where('slug', $category));
        }
        if ($brand = $request->query('merek')) {
            $query->where('brand', $brand);
        }
        if ($min = $request->query('harga_min')) {
            $query->where('price_daily', '>=', (int) $min);
        }
        if ($max = $request->query('harga_max')) {
            $query->where('price_daily', '<=', (int) $max);
        }
        match ($request->query('cc')) {
            'sd150' => $query->where('cc', '<=', 150),
            '151-200' => $query->whereBetween('cc', [151, 200]),
            'gt200' => $query->where('cc', '>', 200),
            default => null,
        };
        if (in_array($request->query('transmisi'), ['Manual', 'Automatic'], true)) {
            $query->where('transmission', $request->query('transmisi'));
        }
        if (in_array($request->query('status'), ['available', 'maintenance'], true)) {
            $query->where('status', $request->query('status'));
        }

        // Filter ketersediaan berdasarkan tanggal (dari kotak pencarian di beranda): sembunyikan yang stoknya habis.
        if ($request->filled('start') && $request->filled('end')) {
            $start = Carbon::parse($request->query('start'))->startOfDay();
            $end = Carbon::parse($request->query('end'))->startOfDay();
            $blocked = Motorcycle::visible()->where('status', 'available')->get()
                ->filter(fn (Motorcycle $m) => $this->booking->unitsLeft($m, $start, $this->booking->normalizeEnd($m, $start, $end)) < 1)
                ->pluck('id');
            $query->where('status', 'available')->whereNotIn('id', $blocked);
        }

        match ($request->query('urut', 'populer')) {
            'terbaru' => $query->latest('id'),
            'termurah' => $query->orderBy('price_daily'),
            'termahal' => $query->orderByDesc('price_daily'),
            default => $query->orderByDesc('is_popular')->orderByDesc('rental_items_count'),
        };

        return view('motors.index', [
            'motors' => $query->paginate(9)->withQueryString(),
            'categories' => MotorCategory::orderBy('name')->get(),
            'brands' => Motorcycle::visible()->distinct()->orderBy('brand')->pluck('brand'),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(string $slug)
    {
        $motor = Motorcycle::visible()->with(['images', 'category'])->where('slug', $slug)->firstOrFail();

        $booked = collect($this->booking->fullDays($motor))
            ->map(fn ($d) => ['start' => $d, 'end' => Carbon::parse($d)->addDay()->toDateString()])->values();

        return view('motors.show', [
            'motor' => $motor,
            'booked' => $booked,
            'packages' => $motor->isJeep() ? RentalPackage::active()->where('vehicle_type', 'jeep')->get() : collect(),
            'addons' => Addon::for('rental')->get(),
            'reviews' => $motor->approvedReviews()->with('user')->latest()->take(6)->get(),
            'avgRating' => round((float) $motor->approvedReviews()->avg('rating'), 1),
            'related' => Motorcycle::visible()->with('images')->where('id', '!=', $motor->id)
                ->where('vehicle_type', $motor->vehicle_type)->where('status', 'available')->inRandomOrder()->take(3)->get(),
        ]);
    }
}
