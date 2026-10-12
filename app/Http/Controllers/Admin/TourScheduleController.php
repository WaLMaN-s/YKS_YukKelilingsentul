<?php

namespace App\Http\Controllers\Admin;

use App\Models\TourPackage;
use App\Models\TourSchedule;
use Illuminate\Database\Eloquent\Builder;

class TourScheduleController extends CrudController
{
    protected string $model = TourSchedule::class;

    protected string $routeBase = 'admin.jadwal-wisata';

    protected string $title = 'Jadwal Wisata';

    protected string $singular = 'Jadwal';

    protected function query(): Builder
    {
        return TourSchedule::query()->with('package')->orderByDesc('date');
    }

    protected function fields(): array
    {
        return [
            'tour_package_id' => ['label' => 'Paket wisata', 'type' => 'select', 'options' => TourPackage::orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'required', 'list' => true],
            'date' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => 'required', 'list' => true],
            'quota' => ['label' => 'Kuota', 'type' => 'number', 'rules' => 'required|integer|min:1', 'list' => true],
            'booked' => ['label' => 'Sudah terisi', 'type' => 'number', 'rules' => 'integer|min:0', 'default' => 0, 'list' => true],
            'notes' => ['label' => 'Catatan', 'type' => 'text', 'rules' => 'nullable|max:200'],
        ];
    }
}
