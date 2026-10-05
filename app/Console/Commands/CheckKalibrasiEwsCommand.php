<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Services\EarlyWarningService;
use Illuminate\Console\Command;

class CheckKalibrasiEwsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ews:check-kalibrasi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pengecekan Early Warning System (EWS) kalibrasi alkes untuk H-30, H-7, dan unit yang telah kadaluarsa serta penerbitan notifikasi ke elektromedis';

    /**
     * Execute the console command.
     */
    public function handle(EarlyWarningService $ewsService): int
    {
        $this->info('Memulai pengecekan Early Warning System (EWS) Kalibrasi Alkes...');

        $result = $ewsService->checkAndGenerateNotifications();

        $today = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in7Days = now()->addDays(7)->toDateString();

        $countExpired = Alkes::whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '<', $today)
            ->count();

        $countH7 = Alkes::whereNotNull('tanggal_kalibrasi_berikutnya')
            ->whereBetween('tanggal_kalibrasi_berikutnya', [$today, $in7Days])
            ->count();

        $countH30 = Alkes::whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '>', $in7Days)
            ->where('tanggal_kalibrasi_berikutnya', '<=', $in30Days)
            ->count();

        $this->newLine();
        $this->table(
            ['Kategori Peringatan EWS', 'Jumlah Alkes Aktif', 'Notifikasi Baru Dibuat'],
            [
                ['Expired (Masa Kalibrasi Habis)', $countExpired, $result['expired_created']],
                ['Kritis H-7 (Jatuh tempo <= 7 Hari)', $countH7, $result['h7_created']],
                ['Peringatan H-30 (Jatuh tempo 8 - 30 Hari)', $countH30, $result['h30_created']],
            ]
        );

        if ($result['total_created'] > 0) {
            $this->info("Berhasil menerbitkan {$result['total_created']} notifikasi baru untuk Instalasi Elektromedis.");
        } else {
            $this->info("Tidak ada notifikasi baru yang perlu diterbitkan (semua notifikasi aktif telah tersinkronisasi).");
        }

        return Command::SUCCESS;
    }
}
