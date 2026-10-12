<?php

namespace App\Http\Controllers\Admin;

use App\Models\TourPackage;

class TourPackageController extends CrudController
{
    protected string $model = TourPackage::class;

    protected string $routeBase = 'admin.paket-wisata';

    protected string $title = 'Paket Wisata';

    protected string $singular = 'Paket wisata';

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama paket', 'type' => 'text', 'rules' => 'required|max:120', 'list' => true],
            'slug' => ['label' => 'Slug', 'type' => 'text', 'rules' => 'nullable|max:140', 'hint' => 'Kosongkan untuk dibuat otomatis.'],
            'price' => ['label' => 'Harga per orang (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0', 'money' => true, 'list' => true],
            'duration' => ['label' => 'Durasi', 'type' => 'text', 'rules' => 'required|max:50', 'list' => true, 'hint' => 'Contoh: 1 Hari, 4 Jam.'],
            'destination' => ['label' => 'Destinasi', 'type' => 'text', 'rules' => 'nullable|max:200'],
            'own_motor' => ['label' => 'Paket bawa motor pribadi', 'type' => 'checkbox', 'list' => true, 'hint' => 'Peserta membawa motor sendiri (tanpa sewa motor).'],
            'quota' => ['label' => 'Kuota per jadwal', 'type' => 'number', 'rules' => 'required|integer|min:1', 'list' => true],
            'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => 'nullable|max:3000'],
            'facilities' => ['label' => 'Fasilitas', 'type' => 'lines', 'hint' => 'Satu fasilitas per baris.'],
            'image' => ['label' => 'Foto', 'type' => 'image'],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'list' => true],
        ];
    }
}
