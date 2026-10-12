<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return redirect($notification->url ?: url()->previous());
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
