<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Motorcycle;
use App\Models\MotorCategory;
use App\Models\MotorImage;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MotorController extends Controller
{
    public function index(Request $request)
    {
        $motors = Motorcycle::with(['images', 'category'])->withCount('rentalItems')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')->orWhere('plate_number', 'like', '%'.$request->q.'%')->orWhere('brand', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('jenis'), fn ($q) => $q->where('vehicle_type', $request->jenis))
            ->latest('id')->paginate(12)->withQueryString();

        return view('admin.motors.index', ['motors' => $motors]);
    }

    public function create()
    {
        return view('admin.motors.form', ['motor' => new Motorcycle(['status' => 'available', 'transmission' => 'Manual', 'capacity' => 2, 'stock' => 1, 'vehicle_type' => 'trail']), 'categories' => MotorCategory::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $motor = Motorcycle::create($data);
        $this->storeImages($request, $motor);

        return redirect()->route('admin.motor.index')->with('success', 'Motor berhasil ditambahkan.');
    }

    public function show(Motorcycle $motor)
    {
        $motor->load(['images', 'category'])->loadCount('rentalItems');

        return view('admin.motors.show', [
            'motor' => $motor,
            'history' => $motor->rentalItems()->with('rental')->latest()->take(8)->get(),
        ]);
    }

    public function edit(Motorcycle $motor)
    {
        return view('admin.motors.form', ['motor' => $motor->load('images'), 'categories' => MotorCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, Motorcycle $motor): RedirectResponse
    {
        $motor->update($this->validated($request, $motor));
        $this->storeImages($request, $motor);

        return redirect()->route('admin.motor.index')->with('success', 'Motor berhasil diperbarui.');
    }

    public function destroy(Motorcycle $motor): RedirectResponse
    {
        if ($motor->rentalItems()->exists()) {
            return back()->with('error', 'Motor punya riwayat penyewaan dan tidak bisa dihapus. Nonaktifkan saja.');
        }

        $paths = $motor->images->pluck('path');
        try {
            $motor->delete();
        } catch (QueryException) {
            return back()->with('error', 'Motor tidak bisa dihapus karena masih terhubung ke data lain.');
        }
        $paths->reject(fn ($p) => Str::startsWith($p, 'images/'))->each(fn ($p) => Storage::disk('public')->delete($p));

        return redirect()->route('admin.motor.index')->with('success', 'Motor berhasil dihapus.');
    }

    public function status(Request $request, Motorcycle $motor): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::in(array_keys(Motorcycle::STATUSES))]]);
        $motor->update(['status' => $request->status]);

        return back()->with('success', $motor->name.' sekarang berstatus '.$motor->status_label.'.');
    }

    public function destroyImage(MotorImage $image): RedirectResponse
    {
        $wasPrimary = $image->is_primary;
        $motorId = $image->motorcycle_id;
        if (! Str::startsWith($image->path, 'images/')) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();

        if ($wasPrimary) {
            MotorImage::where('motorcycle_id', $motorId)->orderBy('id')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Foto dihapus.');
    }

    public function primaryImage(MotorImage $image): RedirectResponse
    {
        MotorImage::where('motorcycle_id', $image->motorcycle_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Foto utama diperbarui.');
    }

    private function validated(Request $request, ?Motorcycle $motor): array
    {
        $data = $request->validate([
            'vehicle_type' => ['required', Rule::in(array_keys(Motorcycle::TYPES))],
            'stock' => ['required', 'integer', 'between:0,1000'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'motor_category_id' => ['nullable', 'exists:motor_categories,id'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:80'],
            'plate_number' => ['nullable', 'string', 'max:20', Rule::unique('motorcycles', 'plate_number')->ignore($motor?->id)],
            'year' => ['nullable', 'integer', 'between:1990,'.(now()->year + 1)],
            'cc' => ['required', 'integer', 'min:50', 'max:2000'],
            'transmission' => ['required', Rule::in(['Manual', 'Automatic'])],
            'capacity' => ['required', 'integer', 'between:1,4'],
            'color' => ['nullable', 'string', 'max:40'],
            'price_daily' => ['required', 'integer', 'min:1000'],
            'price_weekly' => ['nullable', 'integer', 'min:0'],
            'price_monthly' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:3000'],
            'facilities' => ['nullable', 'string', 'max:1500'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(array_keys(Motorcycle::STATUSES))],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $base = Str::slug($data['slug'] ?: $data['name']) ?: Str::random(6);
        $slug = $base;
        $i = 2;
        while (Motorcycle::where('slug', $slug)->when($motor, fn ($q) => $q->where('id', '!=', $motor->id))->exists()) {
            $slug = $base.'-'.$i++;
        }

        $data['slug'] = $slug;
        $data['facilities'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['facilities'] ?? '')))->map('trim')->filter()->values()->all();
        $data['deposit'] = (int) ($data['deposit'] ?? 0);
        $data['is_popular'] = $request->boolean('is_popular');
        $data['is_promo'] = $request->boolean('is_promo');
        unset($data['images']);

        return $data;
    }

    private function storeImages(Request $request, Motorcycle $motor): void
    {
        if (! $request->hasFile('images')) {
            return;
        }
        $hasPrimary = $motor->images()->where('is_primary', true)->exists();
        $order = (int) $motor->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $motor->images()->create([
                'path' => $file->store('motors', 'public'),
                'is_primary' => ! $hasPrimary,
                'sort_order' => ++$order,
            ]);
            $hasPrimary = true;
        }
    }
}
