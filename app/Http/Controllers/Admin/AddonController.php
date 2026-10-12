<?php

namespace App\Http\Controllers\Admin;

use App\Models\Addon;

class AddonController extends CrudController
{
    protected string $model = Addon::class;

    protected string $routeBase = 'admin.addon';

    protected string $title = 'Add-on';

    protected string $singular = 'Add-on';

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama add-on', 'type' => 'text', 'rules' => 'required|max:100', 'list' => true],
            'price' => ['label' => 'Harga (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0', 'money' => true, 'list' => true],
            'per' => ['label' => 'Dihitung', 'type' => 'select', 'options' => Addon::PER, 'rules' => 'required', 'list' => true, 'hint' => 'Per orang = harga × jumlah peserta/pengendara. Per booking = sekali saja.'],
            'applies_to' => ['label' => 'Berlaku untuk', 'type' => 'select', 'options' => Addon::TARGETS, 'rules' => 'required', 'list' => true],
            'description' => ['label' => 'Keterangan', 'type' => 'textarea', 'rules' => 'nullable|max:300'],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'list' => true],
        ];
    }
}
