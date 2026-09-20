<?php

namespace Database\Seeders;

use App\Services\GoogleSheetSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Mengambil data langsung secara live dari Google Spreadsheet resmi ZAPIN
     * dan menyimpannya ke PostgreSQL secara atomik.
     */
    public function run(GoogleSheetSyncService $syncService): void
    {
        if (isset($this->command)) {
            $this->command->info('Memulai seeding database ZAPIN langsung dari Google Spreadsheet...');
        }

        try {
            $result = $syncService->sync();
            if (isset($this->command)) {
                $this->command->info("Seeding berhasil! {$result['created']} data alkes baru, {$result['updated']} data diperbarui (Total {$result['ruangan_count']} ruangan).");
            }
        } catch (\Throwable $e) {
            if (isset($this->command)) {
                $this->command->warn('Peringatan: Sinkronisasi online Google Spreadsheet saat seeder: ' . $e->getMessage());
            }
            Log::warning('DatabaseSeeder Google Spreadsheet sync notice: ' . $e->getMessage());
        }
    }
}

