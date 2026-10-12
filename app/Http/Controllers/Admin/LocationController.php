<?php

namespace App\Http\Controllers\Admin;

use App\Models\Location;
use Illuminate\Database\Eloquent\Model;

class LocationController extends CrudController
{
    protected string $model = Location::class;

    protected string $routeBase = 'admin.lokasi';

    protected string $title = 'Lokasi Pengambilan';

    protected string $singular = 'Lokasi';

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama lokasi', 'type' => 'text', 'rules' => 'required|max:100', 'list' => true],
            'address' => ['label' => 'Alamat', 'type' => 'textarea', 'rules' => 'nullable|max:500', 'list' => true],
            'delivery_fee' => ['label' => 'Biaya antar (Rp)', 'type' => 'number', 'rules' => 'integer|min:0', 'default' => 0, 'money' => true, 'list' => true, 'hint' => '0 = ambil di basecamp / gratis.'],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function cannotDelete(Model $item): ?string
    {
        return $item->rentals()->exists() ? 'Lokasi sudah dipakai di data penyewaan. Nonaktifkan saja.' : null;
    }
}
