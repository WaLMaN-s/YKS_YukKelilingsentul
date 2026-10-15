<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Basis CRUD sederhana untuk data master. Setiap turunan cukup mendefinisikan fields().
 *
 * Tipe field: text, email, number, textarea, select, checkbox, date, image, lines, password.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    protected string $routeBase;

    protected string $title;

    protected string $singular;

    /** @return array<string, array<string, mixed>> */
    abstract protected function fields(): array;

    protected function query(): Builder
    {
        return ($this->model)::query()->latest('id');
    }

    public function index()
    {
        return view('admin.crud.index', [
            'ctl' => $this,
            'items' => $this->query()->paginate(15)->withQueryString(),
            'columns' => collect($this->fields())->filter(fn ($f) => $f['list'] ?? false),
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', ['ctl' => $this, 'item' => new ($this->model), 'fields' => $this->fields()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->prepare($request, null);
        $item = ($this->model)::create($data);
        $this->afterSave($item, $request);

        return redirect()->route($this->routeBase.'.index')->with('success', $this->singular.' berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        return view('admin.crud.form', ['ctl' => $this, 'item' => $this->find($id), 'fields' => $this->fields()]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->find($id);
        $item->update($this->prepare($request, $item));
        $this->afterSave($item, $request);

        return redirect()->route($this->routeBase.'.index')->with('success', $this->singular.' berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = $this->find($id);
        if ($error = $this->cannotDelete($item)) {
            return back()->with('error', $error);
        }

        try {
            $item->delete();
        } catch (QueryException) {
            return back()->with('error', $this->singular.' tidak bisa dihapus karena masih dipakai data lain. Nonaktifkan saja.');
        }
        foreach ($this->fields() as $name => $field) {
            if (($field['type'] ?? '') === 'image' && $item->{$name} && ! Str::startsWith($item->{$name}, 'images/')) {
                Storage::disk('public')->delete($item->{$name});
            }
        }

        return redirect()->route($this->routeBase.'.index')->with('success', $this->singular.' berhasil dihapus.');
    }

    // ---- hook yang bisa di-override ----

    protected function cannotDelete(Model $item): ?string
    {
        return null;
    }

    protected function transform(array $data, ?Model $item): array
    {
        return $data;
    }

    protected function afterSave(Model $item, Request $request): void {}

    // ---- helper untuk view ----

    public function title(): string
    {
        return $this->title;
    }

    public function singular(): string
    {
        return $this->singular;
    }

    public function routeBase(): string
    {
        return $this->routeBase;
    }

    public function display(Model $item, string $name, array $field): string
    {
        $value = $item->{$name};

        return match ($field['type'] ?? 'text') {
            'select' => (string) ($field['options'][$value] ?? '-'),
            'checkbox' => $value ? 'Aktif' : 'Nonaktif',
            'lines' => count((array) $value).' item',
            'date' => tgl($value),
            'number' => ($field['money'] ?? false) ? rupiah($value) : (string) ($value ?? '-'),
            default => Str::limit((string) ($value ?? '-'), 60),
        };
    }

    // ---- internal ----

    protected function find(string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    protected function prepare(Request $request, ?Model $item): array
    {
        $fields = $this->fields();
        $rules = [];
        $attributes = [];
        $messages = [];
        foreach ($fields as $name => $field) {
            $rules[$name] = $this->rulesFor($name, $field, $item);
            $attributes[$name] = $field['label'];
            if (($field['type'] ?? '') === 'image') {
                $messages[$name.'.uploaded'] = $field['label'].' gagal diunggah — ukurannya melebihi batas server ('.ini_get('upload_max_filesize').'). Perkecil foto, atau naikkan upload_max_filesize & post_max_size di php.ini.';
            }
        }
        $validated = $request->validate($rules, $messages, $attributes);

        $data = [];
        foreach ($fields as $name => $field) {
            switch ($field['type']) {
                case 'image':
                    if ($request->hasFile($name)) {
                        // Simpan berkas baru lebih dulu; jangan hapus yang lama sebelum berhasil.
                        $stored = $request->file($name)->store('uploads/'.Str::slug($this->routeBase), 'public');

                        if (! $stored) {
                            throw ValidationException::withMessages([
                                $name => 'Foto gagal disimpan ke storage/app/public. Pastikan foldernya dapat ditulis web server dan symlink sudah dibuat (php artisan storage:link).',
                            ]);
                        }

                        if ($item && $item->{$name} && ! Str::startsWith($item->{$name}, 'images/')) {
                            Storage::disk('public')->delete($item->{$name});
                        }

                        $data[$name] = $stored;
                    }

                    break;
                case 'checkbox':
                    $data[$name] = $request->boolean($name);
                    break;
                case 'lines':
                    $data[$name] = collect(preg_split('/\r\n|\r|\n/', (string) $request->input($name)))
                        ->map(fn ($l) => trim($l))->filter()->values()->all();
                    break;
                case 'password':
                    if (filled($validated[$name] ?? null)) {
                        $data[$name] = $validated[$name];
                    }
                    break;
                default:
                    $value = $validated[$name] ?? null;
                    if ($value === null && array_key_exists('default', $field)) {
                        $value = $field['default'];
                    }
                    $data[$name] = $value;
            }
        }

        if (array_key_exists('slug', $data)) {
            $base = Str::slug($data['slug'] ?: ($data['name'] ?? ''));
            $data['slug'] = $this->uniqueSlug($base ?: Str::random(6), $item);
        }

        return $this->transform($data, $item);
    }

    protected function rulesFor(string $name, array $field, ?Model $item): array
    {
        $rules = (array) ($field['rules'] ?? []);
        if (count($rules) === 1 && is_string($rules[0]) && str_contains($rules[0], '|')) {
            $rules = explode('|', $rules[0]);
        }

        switch ($field['type']) {
            case 'image':
                $rules[] = ($field['required_on_create'] ?? false) && ! $item ? 'required' : 'nullable';
                array_push($rules, 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096');
                break;
            case 'checkbox':
                $rules = ['nullable', 'boolean'];
                break;
            case 'select':
                $rules[] = Rule::in(array_keys($field['options'] ?? []));
                break;
            case 'password':
                array_unshift($rules, ($field['required_on_create'] ?? false) && ! $item ? 'required' : 'nullable');
                $rules[] = 'min:8';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
        }
        if (isset($field['unique'])) {
            $rules[] = Rule::unique($field['unique'], $name)->ignore($item?->getKey());
        }
        if (! in_array('required', $rules, true) && ! in_array('nullable', $rules, true)) {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    protected function uniqueSlug(string $base, ?Model $item): string
    {
        $slug = $base;
        $i = 2;
        while (($this->model)::where('slug', $slug)->when($item, fn ($q) => $q->where('id', '!=', $item->getKey()))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
