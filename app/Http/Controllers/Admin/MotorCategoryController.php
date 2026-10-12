<?php

namespace App\Http\Controllers\Admin;

use App\Models\MotorCategory;
use Illuminate\Database\Eloquent\Model;

class MotorCategoryController extends CrudController
{
    protected string $model = MotorCategory::class;

    protected string $routeBase = 'admin.kategori-motor';

    protected string $title = 'Kategori Motor';

    protected string $singular = 'Kategori';

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama kategori', 'type' => 'text', 'rules' => 'required|max:80', 'list' => true],
            'slug' => ['label' => 'Slug', 'type' => 'text', 'rules' => 'nullable|max:100', 'hint' => 'Kosongkan untuk dibuat otomatis.', 'list' => true],
            'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => 'nullable|max:500'],
        ];
    }

    protected function cannotDelete(Model $item): ?string
    {
        return $item->motorcycles()->exists() ? 'Kategori masih dipakai oleh motor.' : null;
    }
}
