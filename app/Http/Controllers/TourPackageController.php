<?php

namespace App\Http\Controllers;

use App\Models\TourPackage;

class TourPackageController extends Controller
{
    public function index()
    {
        return view('tours.index', [
            'tours' => TourPackage::where('is_active', true)->withCount(['schedules as upcoming_schedules' => fn ($q) => $q->where('date', '>=', today())])->latest('id')->get(),
        ]);
    }

    public function show(string $slug)
    {
        $tour = TourPackage::where('is_active', true)->where('slug', $slug)->firstOrFail();

        return view('tours.show', [
            'tour' => $tour,
            'schedules' => $tour->schedules()->where('date', '>=', today())->get(),
            'others' => TourPackage::where('is_active', true)->where('id', '!=', $tour->id)->take(3)->get(),
        ]);
    }
}
