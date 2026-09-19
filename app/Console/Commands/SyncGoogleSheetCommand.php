<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetSyncService;
use Illuminate\Console\Command;

class SyncGoogleSheetCommand extends Command
{
    protected $signature = 'zapin:sync-sheets {--url= : URL opsional Google Spreadsheet}';
    protected $description = 'Sinkronisasi data alkes dan ruangan langsung dari Google Spreadsheet ke PostgreSQL ZAPIN';

    public function handle(GoogleSheetSyncService $syncService): int
    {
        $this->info('Memulai sinkronisasi data dari Google Spreadsheet...');

        try {
            $customUrl = $this->option('url');
            if ($customUrl) {
                $syncService = new GoogleSheetSyncService($customUrl);
            }

            $result = $syncService->sync();

            $this->newLine();
            $this->table(
                ['Metrik Sinkronisasi', 'Jumlah'],
                [
                    ['Total Baris Diproses', $result['total']],
                    ['Alkes Baru Ditambahkan', $result['created']],
                    ['Alkes Diperbarui', $result['updated']],
                    ['Total Ruangan Aktif', $result['ruangan_count']],
                ]
            );

            $this->info('Sinkronisasi hybrid Google Spreadsheet berhasil diselesaikan.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal melakukan sinkronisasi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

