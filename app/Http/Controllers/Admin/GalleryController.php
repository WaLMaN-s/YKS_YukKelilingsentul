<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gallery;

class GalleryController extends CrudController
{
    protected string $model = Gallery::class;

    protected string $routeBase = 'admin.galeri';

    protected string $title = 'Galeri';

    protected string $singular = 'Foto';

    protected function fields(): array
    {
        return [
            'title' => ['label' => 'Judul', 'type' => 'text', 'rules' => 'required|max:120', 'list' => true],
            'category' => ['label' => 'Kategori', 'type' => 'select', 'options' => Gallery::CATEGORIES, 'rules' => 'required', 'list' => true],
            'image' => ['label' => 'Foto', 'type' => 'image', 'required_on_create' => true, 'list' => true],
            'is_active' => ['label' => 'Tampilkan', 'type' => 'checkbox', 'list' => true],
        ];
    }
}
