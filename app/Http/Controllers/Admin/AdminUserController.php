<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminUserController extends CrudController
{
    protected string $model = User::class;

    protected string $routeBase = 'admin.admin-users';

    protected string $title = 'Admin Users';

    protected string $singular = 'Admin';

    protected function query(): Builder
    {
        return User::query()->staffAccounts()->with('role')->latest('id');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama', 'type' => 'text', 'rules' => 'required|max:100', 'list' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'rules' => 'required|email|max:150', 'unique' => 'users', 'list' => true],
            'phone' => ['label' => 'No. HP', 'type' => 'text', 'rules' => 'nullable|max:30'],
            'role_id' => ['label' => 'Role', 'type' => 'select', 'rules' => 'required', 'list' => true,
                'options' => Role::where('slug', '!=', 'customer')->orderBy('id')->pluck('name', 'id')->all(),
                'hint' => 'Super Admin: akses penuh • Admin: operasional • Kasir: rental & pembayaran • Staff: motor, pickup, return.'],
            'password' => ['label' => 'Password', 'type' => 'password', 'required_on_create' => true, 'hint' => 'Kosongkan saat edit jika tidak diganti. Minimal 8 karakter.'],
            'is_blocked' => ['label' => 'Blokir akun', 'type' => 'checkbox', 'list' => true],
        ];
    }

    public function display(Model $item, string $name, array $field): string
    {
        if ($name === 'is_blocked') {
            return $item->is_blocked ? 'Diblokir' : 'Aktif';
        }

        return parent::display($item, $name, $field);
    }

    protected function cannotDelete(Model $item): ?string
    {
        if ($item->id === auth()->id()) {
            return 'Anda tidak bisa menghapus akun sendiri.';
        }

        return $item->rentals()->exists() ? 'Akun ini punya riwayat transaksi. Blokir saja.' : null;
    }
}
