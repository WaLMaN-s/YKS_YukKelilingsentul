<?php

namespace App\Http\Controllers\Admin;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodController extends CrudController
{
    protected string $model = PaymentMethod::class;

    protected string $routeBase = 'admin.metode-pembayaran';

    protected string $title = 'Metode Pembayaran';

    protected string $singular = 'Metode pembayaran';

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama (mis. BCA, QRIS)', 'type' => 'text', 'rules' => 'required|max:80', 'list' => true],
            'type' => ['label' => 'Jenis', 'type' => 'select', 'options' => PaymentMethod::TYPES, 'rules' => 'required', 'list' => true],
            'account_name' => ['label' => 'Atas nama', 'type' => 'text', 'rules' => 'nullable|max:100', 'list' => true],
            'account_number' => ['label' => 'Nomor rekening / ID', 'type' => 'text', 'rules' => 'nullable|max:60', 'list' => true],
            'instructions' => ['label' => 'Petunjuk pembayaran', 'type' => 'textarea', 'rules' => 'nullable|max:1000'],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function cannotDelete(Model $item): ?string
    {
        return $item->payments()->exists() ? 'Metode sudah dipakai pada pembayaran. Nonaktifkan saja.' : null;
    }
}
