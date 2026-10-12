<?php

namespace App\Http\Controllers\Admin;

use App\Models\RentalPackage;
use Illuminate\Database\Eloquent\Model;

class RentalPackageController extends CrudController
{
    protected string $model = RentalPackage::class;

    protected string $routeBase = 'admin.paket-kendaraan';

    protected string $title = 'Paket Jeep';

    protected string $singular = 'Paket';

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return RentalPackage::query()->orderBy('vehicle_type')->orderBy('sort_order')->orderBy('price');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama paket', 'type' => 'text', 'rules' => 'required|max:120', 'list' => true],
            'vehicle_type' => ['label' => 'Untuk kendaraan', 'type' => 'select', 'options' => ['jeep' => 'Jeep'], 'rules' => 'required', 'list' => true],
            'price' => ['label' => 'Harga per kendaraan (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0', 'money' => true, 'list' => true, 'hint' => 'Harga per jeep untuk satu perjalanan (sudah termasuk semua isi paket).'],
            'includes' => ['label' => 'Isi paket', 'type' => 'lines', 'hint' => 'Satu item per baris (mis. Jeep + driver, Trekking, Tiket curug).'],
            'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => 'nullable|max:1000'],
            'sort_order' => ['label' => 'Urutan tampil', 'type' => 'number', 'rules' => 'integer|min:0', 'default' => 0],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function cannotDelete(Model $item): ?string
    {
        return $item->rentals()->exists() ? 'Paket sudah dipakai pada booking. Nonaktifkan saja.' : null;
    }
}
