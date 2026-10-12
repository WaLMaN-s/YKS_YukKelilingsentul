<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['user', 'motorcycle', 'rental'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(12)->withQueryString();

        return view('admin.reviews', ['reviews' => $reviews]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['status' => 'approved']);

        return back()->with('success', 'Review disetujui dan tampil di website.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->update(['status' => 'rejected']);

        return back()->with('success', 'Review ditolak.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Review dihapus.');
    }
}
