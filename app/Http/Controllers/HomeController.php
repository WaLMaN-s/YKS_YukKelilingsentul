<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Location;
use App\Models\Motorcycle;
use App\Models\Review;
use App\Models\TourPackage;

class HomeController extends Controller
{
    public function index()
    {
        $motors = Motorcycle::visible()->ofType('trail')->with('images')
            ->withAvg(['reviews as avg_rating' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->orderByDesc('is_popular')->orderBy('price_daily')->take(4)->get();

        return view('pages.home', [
            'motors' => $motors,
            'tours' => TourPackage::where('is_active', true)->latest('id')->take(3)->get(),
            'reviews' => Review::approved()->with(['user', 'motorcycle'])->latest()->take(3)->get(),
            'galleries' => Gallery::where('is_active', true)->latest('id')->take(8)->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'jeeps' => Motorcycle::visible()->ofType('jeep')->with('images')->orderBy('price_daily')->take(3)->get(),
            'unitCounts' => ['trail' => (int) Motorcycle::visible()->ofType('trail')->sum('stock'), 'jeep' => (int) Motorcycle::visible()->ofType('jeep')->sum('stock')],
            'facilities' => preg_split('/\r\n|\r|\n/', (string) setting('basecamp_facilities', ''), -1, PREG_SPLIT_NO_EMPTY),
        ]);
    }
}
