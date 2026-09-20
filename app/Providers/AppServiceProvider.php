<?php

namespace App\Providers;

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
            $currentRole = session('user_role');
            $userRoleLabel = session('user_role_label', 'Pengguna ZAPIN');

            $recentNotifs = collect([]);
            $unreadNotifCount = 0;

            if ($currentRole === 'elektromedis') {
                try {
                    $pendingLogs = \App\Models\LogPemeliharaan::with(['alkes.ruangan'])
                        ->where('status_hasil', 'Proses')
                        ->latest()
                        ->take(5)
                        ->get();

                    $unreadNotifCount = \App\Models\LogPemeliharaan::where('status_hasil', 'Proses')->count();

                    $recentNotifs = $pendingLogs->map(function ($item) {
                        return (object) [
                            'judul' => 'Perbaikan: ' . ($item->alkes->nama_barang ?? 'Alkes'),
                            'pesan' => 'Ruang ' . ($item->alkes->ruangan->nama_ruangan ?? 'RS') . ': ' . \Illuminate\Support\Str::limit($item->deskripsi_kerusakan ?? '-', 65),
                            'created_at' => $item->created_at ?? now(),
                            'dibaca' => false,
                        ];
                    });
                } catch (\Throwable $e) {
                    // Fallback jika database belum dimigrasi
                }
            }

            $view->with([
                'unreadNotifCount' => $unreadNotifCount,
                'recentNotifs' => $recentNotifs,
                'currentRole' => $currentRole,
                'userRoleLabel' => $userRoleLabel,
            ]);
        });
    }
}
