<?php

namespace App\Providers;

use App\Models\LogPemeliharaan;
use App\Models\Notification;
use App\Services\EarlyWarningService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
                    // Jalankan pengecekan EWS otomatis secara berkala (throttle 60 detik via cache)
                    if (!Cache::has('ews_auto_check_throttle')) {
                        app(EarlyWarningService::class)->checkAndGenerateNotifications();
                        Cache::put('ews_auto_check_throttle', true, 60);
                    }

                    $ewsNotifs = Notification::where('target_role', 'elektromedis')
                        ->latest()
                        ->take(8)
                        ->get();

                    $pendingLogs = LogPemeliharaan::with(['alkes.ruangan'])
                        ->where('status_hasil', 'Proses')
                        ->latest()
                        ->take(5)
                        ->get();

                    $unreadEws = Notification::where('target_role', 'elektromedis')
                        ->where('is_read', false)
                        ->count();

                    $unreadPendingLogs = LogPemeliharaan::where('status_hasil', 'Proses')->count();
                    $unreadNotifCount = $unreadEws + $unreadPendingLogs;

                    $formattedEws = $ewsNotifs->map(function ($item) {
                        return (object) [
                            'id' => $item->id,
                            'judul' => $item->judul,
                            'pesan' => $item->pesan,
                            'stage' => $item->stage,
                            'level' => $item->level,
                            'url' => $item->url,
                            'created_at' => $item->created_at ?? now(),
                            'dibaca' => (bool) $item->is_read,
                            'type' => $item->type,
                            'badgeClasses' => $item->badgeClasses(),
                        ];
                    });

                    $formattedLogs = $pendingLogs->map(function ($item) {
                        return (object) [
                            'id' => null,
                            'judul' => 'Perbaikan: ' . ($item->alkes->nama_barang ?? 'Alkes'),
                            'pesan' => 'Ruang ' . ($item->alkes->ruangan->nama_ruangan ?? 'RS') . ': ' . Str::limit($item->deskripsi_kerusakan ?? '-', 65),
                            'stage' => 'PERBAIKAN',
                            'level' => 'info',
                            'url' => route('pemeliharaan.index'),
                            'created_at' => $item->created_at ?? now(),
                            'dibaca' => false,
                            'type' => 'pemeliharaan',
                            'badgeClasses' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                        ];
                    });

                    $recentNotifs = $formattedEws->concat($formattedLogs)
                        ->sortByDesc('created_at')
                        ->take(8)
                        ->values();

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
