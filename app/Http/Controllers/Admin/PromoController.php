<?php

namespace App\Http\Controllers\Admin;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Model;

class PromoController extends CrudController
{
    protected string $model = Promo::class;

    protected string $routeBase = 'admin.promo';

    protected string $title = 'Promo';

    protected string $singular = 'Promo';

    protected function fields(): array
    {
        return [
            'code' => ['label' => 'Kode promo', 'type' => 'text', 'rules' => 'required|max:30|alpha_dash', 'unique' => 'promos', 'list' => true, 'hint' => 'Contoh: HEMAT20 (otomatis huruf besar).'],
            'type' => ['label' => 'Jenis diskon', 'type' => 'select', 'options' => Promo::TYPES, 'rules' => 'required', 'list' => true],
            'value' => ['label' => 'Nilai diskon', 'type' => 'number', 'rules' => 'required|integer|min:1', 'list' => true, 'hint' => 'Persen (mis. 20) atau rupiah (mis. 50000) sesuai jenis.'],
            'min_transaction' => ['label' => 'Minimal transaksi (Rp)', 'type' => 'number', 'rules' => 'integer|min:0', 'default' => 0, 'money' => true],
            'max_discount' => ['label' => 'Maksimal diskon (Rp)', 'type' => 'number', 'rules' => 'nullable|integer|min:1', 'money' => true],
            'starts_at' => ['label' => 'Mulai', 'type' => 'date', 'list' => true],
            'ends_at' => ['label' => 'Berakhir', 'type' => 'date', 'rules' => 'nullable|after_or_equal:starts_at', 'list' => true],
            'quota' => ['label' => 'Kuota pemakaian', 'type' => 'number', 'rules' => 'nullable|integer|min:1', 'hint' => 'Kosongkan jika tanpa batas.', 'list' => true],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function transform(array $data, ?Model $item): array
    {
        $data['code'] = strtoupper($data['code']);

        return $data;
    }

    protected function cannotDelete(Model $item): ?string
    {
        return $item->usages()->exists() ? 'Promo sudah pernah dipakai. Nonaktifkan saja.' : null;
    }
}
