<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Alkes;
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
    protected $description = 'Pengecekan Early Warning System (EWS) kalibrasi alkes untuk H-30, H-7, dan unit yang telah kadaluarsa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pengecekan Early Warning System (EWS) Kalibrasi Alkes...');

        $today = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in7Days = now()->addDays(7)->toDateString();

        // 1. Alkes yang telah expired
        $expiredList = Alkes::with('ruangan')
            ->whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '<', $today)
            ->orderBy('tanggal_kalibrasi_berikutnya', 'asc')
            ->get();

        // 2. Alkes yang jatuh tempo dalam H-7
        $h7List = Alkes::with('ruangan')
            ->whereNotNull('tanggal_kalibrasi_berikutnya')
            ->whereBetween('tanggal_kalibrasi_berikutnya', [$today, $in7Days])
            ->orderBy('tanggal_kalibrasi_berikutnya', 'asc')
            ->get();

        // 3. Alkes yang jatuh tempo dalam H-30 (tetapi di luar H-7)
        $h30List = Alkes::with('ruangan')
            ->whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '>', $in7Days)
            ->where('tanggal_kalibrasi_berikutnya', '<=', $in30Days)
            ->orderBy('tanggal_kalibrasi_berikutnya', 'asc')
            ->get();

        $countExpired = $expiredList->count();
        $countH7 = $h7List->count();
        $countH30 = $h30List->count();

        $this->newLine();
        $this->table(
            ['Kategori Peringatan EWS', 'Jumlah Unit'],
            [
                ['Expired (Masa Kalibrasi Habis)', $countExpired],
                ['Kritis H-7 (Jatuh tempo <= 7 Hari)', $countH7],
                ['Peringatan H-30 (Jatuh tempo 8 - 30 Hari)', $countH30],
            ]
        );

        if ($countExpired > 0 || $countH7 > 0 || $countH30 > 0) {
            $desc = "EWS Kalibrasi: Terdeteksi {$countExpired} unit expired, {$countH7} unit H-7, dan {$countH30} unit H-30.";
            ActivityLog::record(
                'EWS Kalibrasi Otomatis',
                $desc,
                'Instalasi Elektromedis',
                'Sistem EWS'
            );
            $this->info("Peringatan EWS berhasil dicatat ke Activity Log.");
        } else {
            $this->info('Seluruh alat kesehatan dalam kondisi kalibrasi valid. Tidak ada peringatan mendesak.');
        }

        return Command::SUCCESS;
    }
}

