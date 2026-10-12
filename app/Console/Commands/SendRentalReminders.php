<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendRentalReminders extends Command
{
    protected $signature = 'rental:remind';

    protected $description = 'Kirim notifikasi rental hampir selesai (ke customer) dan pengembalian terlambat (ke admin).';

    public function handle(NotificationService $notifier): int
    {
        $soon = Rental::with('item.motorcycle')->where('status', 'rented')
            ->whereDate('end_date', today()->addDay())->get();
        foreach ($soon as $rental) {
            $notifier->toUser($rental->user_id, 'Rental hampir selesai', 'Rental #'.$rental->invoice_no.' berakhir besok. Siapkan motor untuk dikembalikan.', route('dashboard.bookings.show', $rental->id));
        }

        $late = Rental::with('item.motorcycle')->where('status', 'rented')->whereDate('end_date', '<', today())->get();
        foreach ($late as $rental) {
            $notifier->toAdmins(['rental', 'return'], 'Pengembalian terlambat', '#'.$rental->invoice_no.' ('.$rental->renter_name.') belum mengembalikan '.($rental->motorcycle?->name ?? 'motor').'.', route('admin.penyewaan.show', $rental->id));
        }

        $this->info("Pengingat terkirim: {$soon->count()} hampir selesai, {$late->count()} terlambat.");

        return self::SUCCESS;
    }
}
