<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::customers()->with('customer')
            ->withCount('rentals')
            ->withSum(['rentals as spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->when($request->status === 'blocked', fn ($q) => $q->where('is_blocked', true))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    public function show(User $customer)
    {
        abort_unless($customer->isCustomer(), 404);
        $customer->load('customer');

        return view('admin.customers.show', [
            'customer' => $customer,
            'rentals' => $customer->rentals()->with('item.motorcycle')->latest()->get(),
            'payments' => Payment::with(['rental', 'method'])->whereHas('rental', fn ($q) => $q->where('user_id', $customer->id))->latest()->get(),
        ]);
    }

    public function edit(User $customer)
    {
        abort_unless($customer->isCustomer(), 404);

        return view('admin.customers.edit', ['customer' => $customer->load('customer')]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'nik' => ['nullable', 'digits:16'],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'sim_number' => ['nullable', 'string', 'max:30'],
        ]);

        $customer->update(collect($data)->only(['name', 'email', 'phone'])->all());
        $customer->customer()->updateOrCreate(['user_id' => $customer->id], collect($data)->only(['nik', 'address', 'birth_date', 'sim_number'])->all());

        return redirect()->route('admin.customer.show', $customer)->with('success', 'Data customer diperbarui.');
    }

    public function toggleBlock(User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);
        $customer->update(['is_blocked' => ! $customer->is_blocked]);

        return back()->with('success', $customer->is_blocked ? 'Customer diblokir.' : 'Blokir customer dibuka.');
    }
}
