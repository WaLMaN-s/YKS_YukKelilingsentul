<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /** @return array<string, array<string, mixed>> */
    public static function fields(): array
    {
        return [
            'site_name' => ['label' => 'Nama website', 'type' => 'text', 'rules' => 'required|max:80'],
            'tagline' => ['label' => 'Tagline', 'type' => 'text', 'rules' => 'nullable|max:150'],
            'logo' => ['label' => 'Logo', 'type' => 'file'],
            'favicon' => ['label' => 'Favicon', 'type' => 'file'],
            'whatsapp' => ['label' => 'Nomor WhatsApp', 'type' => 'text', 'rules' => 'nullable|max:20', 'hint' => 'Format: 081234567890 atau 6281234567890.'],
            'email' => ['label' => 'Email', 'type' => 'email', 'rules' => 'nullable|email|max:120'],
            'address' => ['label' => 'Alamat', 'type' => 'textarea', 'rules' => 'nullable|max:300'],
            'instagram' => ['label' => 'Instagram', 'type' => 'text', 'rules' => 'nullable|max:80', 'hint' => 'Username tanpa @.'],
            'facebook' => ['label' => 'Facebook', 'type' => 'text', 'rules' => 'nullable|max:120'],
            'tiktok' => ['label' => 'TikTok', 'type' => 'text', 'rules' => 'nullable|max:80', 'hint' => 'Username tanpa @.'],
            'operating_hours' => ['label' => 'Jam operasional', 'type' => 'text', 'rules' => 'nullable|max:120'],
            'min_age' => ['label' => 'Minimal umur penyewa (tahun)', 'type' => 'number', 'rules' => 'required|integer|between:15,60'],
            'min_rental_days' => ['label' => 'Minimal durasi rental (hari)', 'type' => 'number', 'rules' => 'required|integer|between:1,30'],
            'default_deposit' => ['label' => 'Deposit default (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0', 'hint' => 'Dipakai jika deposit motor kosong.'],
            'late_fee_per_hour' => ['label' => 'Denda keterlambatan per jam (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0'],
            'reschedule_min_days' => ['label' => 'Batas ubah jadwal (H-berapa)', 'type' => 'number', 'rules' => 'required|integer|between:0,30', 'hint' => 'Customer boleh mengubah jadwal paling lambat H-n. Default 2 (H-2).'],
            'dp_percent' => ['label' => 'DP pemesanan manual (%)', 'type' => 'number', 'rules' => 'required|integer|between:1,99', 'hint' => 'Hanya untuk pesanan WA/IG yang dicatat admin. Pembayaran di website selalu lunas.'],
            'basecamp_facilities' => ['label' => 'Fasilitas basecamp', 'type' => 'textarea', 'rules' => 'nullable|max:500', 'hint' => 'Satu fasilitas per baris.'],
            'return_time' => ['label' => 'Batas jam pengembalian', 'type' => 'time', 'rules' => 'required|date_format:H:i'],
        ];
    }

    public function edit()
    {
        return view('admin.settings.edit', ['fields' => self::fields(), 'values' => Setting::allCached()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::fields() as $key => $field) {
            $rules[$key] = $field['type'] === 'file' ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg,ico', 'max:2048'] : $field['rules'];
        }
        $data = $request->validate($rules, [], collect(self::fields())->map(fn ($f) => $f['label'])->all());

        $save = [];
        foreach (self::fields() as $key => $field) {
            if ($field['type'] === 'file') {
                if ($request->hasFile($key)) {
                    $old = setting($key);
                    if ($old && ! Str::startsWith($old, 'images/')) {
                        Storage::disk('public')->delete($old);
                    }
                    $save[$key] = $request->file($key)->store('settings', 'public');
                }
            } else {
                $save[$key] = $data[$key] ?? null;
            }
        }
        Setting::put($save);

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
