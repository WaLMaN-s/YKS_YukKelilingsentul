<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.dark');
        Paginator::defaultSimpleView('vendor.pagination.dark');

        // Notifikasi internal untuk navbar customer & admin.
        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            $view->with('unreadCount', $user ? $user->appNotifications()->whereNull('read_at')->count() : 0);
            $view->with('latestNotifications', $user ? $user->appNotifications()->take(6)->get() : collect());
        });
    }
}
