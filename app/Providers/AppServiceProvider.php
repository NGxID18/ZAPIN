<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $unreadNotifCount = Notification::where('dibaca', false)->count();
            $recentNotifs = Notification::with('ruanganAsal')->latest()->take(5)->get();

            $view->with([
                'unreadNotifCount' => $unreadNotifCount,
                'recentNotifs' => $recentNotifs,
                'currentRole' => session('user_role', 'elektromedis'),
                'userRoleLabel' => session('user_role_label', 'Instalasi Elektromedis'),
            ]);
        });
    }
}

