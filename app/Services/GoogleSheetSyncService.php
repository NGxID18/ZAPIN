<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\Ruangan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetSyncService
{
    protected string $sheetUrl;

    protected array $roomCache = [];

    public function __construct(?string $url = null)
    {
        $this->sheetUrl = $url ?? config('zapin.google_sheet_url', '');
    }

    public function getOrCreateRuanganId(?string $rawNama): int
    {
        $nama = trim($rawNama ?? '');
        if (empty($nama) || $nama === '-') {
            $nama = 'G. Penunjang';
        }

        $key = strtolower($nama);
        if (isset($this->roomCache[$key])) {
            return $this->roomCache[$key];
        }

        $ruangan = Ruangan::whereRaw('LOWER(nama_ruangan) = ?', [$key])->first();
        if (!$ruangan) {
            $cleanCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nama), 0, 8));
            $kode = 'R-' . $cleanCode;
            $counter = 1;
            while (Ruangan::where('kode_ruangan', $kode)->exists()) {
                $kode = 'R-' . $cleanCode . '-' . $counter;
                $counter++;
            }

            $ruangan = Ruangan::create([
                'nama_ruangan' => $nama,
                'kode_ruangan' => $kode,
            ]);
        }

        $this->roomCache[$key] = $ruangan->id;
        return $ruangan->id;
    }

    public function sync(): array
    {
        $csvContent = $this->fetchCsv();

        if (empty($csvContent)) {
            throw new \RuntimeException('Gagal mengunduh data CSV dari Google Spreadsheet. Pastikan tautan spreadsheet bersifat publik (view access).');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $created = 0;
        $updated = 0;
        $totalProcessed = 0;

        try {
            DB::transaction(function () use ($stream, &$created, &$updated, &$totalProcessed) {
                $header = null;
                $lineIndex = 0;

                while (($row = fgetcsv($stream)) !== false) {
                    if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                        continue;
                    }

                    if ($lineIndex === 0) {
                        $header = $row;
                        $lineIndex++;
                        continue;
                    }
                    $lineIndex++;

                    if (count($row) < 13) {
                        continue;
                    }

                // Berdasarkan indeks kolom hasil audit profiling:
                // Col 1: No.
                // Col 2: Nama Barang
                // Col 3: Merk
                // Col 4: Tipe
                // Col 5: Serial Number
                // Col 6: Tahun
                // Col 7: Jumlah
                // Col 8: Cara Perolehan
                // Col 9: Nilai Perolehan
                // Col 10: Distributor
                // Col 11: Ruangan
                // Col 12: Lokasi Saat Ini
                // Col 13: Kondisi Alat
                // Col 14: ASPAK
                // Col 15: KIB
                // Col 16: NON KIB dan ASPAK
                // Col 17: AKL/AKD
                // Col 18: KETERANGAN

                $noRaw = trim($row[1] ?? '');
                $namaBarang = trim($row[2] ?? '');

                if (empty($namaBarang)) {
                    continue;
                }

                $noUrut = is_numeric($noRaw) ? (int) $noRaw : null;
                $merk = trim($row[3] ?? '') ?: null;
                $tipe = trim($row[4] ?? '') ?: null;
                $sn = trim($row[5] ?? '') ?: null;
                $tahun = trim($row[6] ?? '') ?: null;
                $jumlahRaw = trim($row[7] ?? '');
                $jumlah = is_numeric($jumlahRaw) ? (int) $jumlahRaw : 1;
                $caraPerolehan = trim($row[8] ?? '') ?: null;
                $nilaiPerolehan = trim($row[9] ?? '') ?: null;
                $distributor = trim($row[10] ?? '') ?: null;
                $ruanganNama = trim($row[11] ?? '');
                $lokasiSaatIniNote = trim($row[12] ?? '') ?: null;
                $kondisiRaw = trim($row[13] ?? '');
                $aspak = trim($row[14] ?? '') ?: null;
                $kib = trim($row[15] ?? '') ?: null;
                $nonKib = trim($row[16] ?? '') ?: null;
                $aklAkd = trim($row[17] ?? '') ?: null;
                $keterangan = trim($row[18] ?? '') ?: null;

                // Tentukan kondisi persis (zero fake data: jika kosong, simpan null)
                $kondisi = !empty($kondisiRaw) ? strtoupper($kondisiRaw) : null;

                // Tentukan status operasional
                $status = 'Tersedia';
                if ($kondisi && str_contains($kondisi, 'RUSAK')) {
                    $status = 'Dalam Perbaikan';
                }

                $ruanganId = $this->getOrCreateRuanganId($ruanganNama);

                // Cari record alkes yang cocok: prioritas utama berdasarkan no_urut spreadsheet (1..638)
                $alkes = null;
                if ($noUrut !== null) {
                    $alkes = Alkes::withTrashed()->where('no_urut', $noUrut)->first();
                    if ($alkes && $alkes->trashed()) {
                        $alkes->restore();
                    }
                }

                $dataPayload = [
                    'no_urut' => $noUrut,
                    'nama_barang' => $namaBarang,
                    'merk' => $merk,
                    'tipe' => $tipe,
                    'nomor_seri' => $sn,
                    'tahun' => $tahun,
                    'jumlah' => $jumlah,
                    'cara_perolehan' => $caraPerolehan,
                    'nilai_perolehan' => $nilaiPerolehan,
                    'distributor' => $distributor,
                    'ruangan_id' => $ruanganId,
                    'lokasi_ruangan_id' => $ruanganId,
                    'lokasi_saat_ini_note' => $lokasiSaatIniNote,
                    'kondisi' => $kondisi,
                    'status' => $status,
                    'aspak' => $aspak,
                    'kib' => $kib,
                    'non_kib_dan_aspak' => $nonKib,
                    'akl_akd' => $aklAkd,
                    'keterangan' => $keterangan,
                ];

                if ($alkes) {
                    $alkes->update($dataPayload);
                    $updated++;
                } else {
                    $dataPayload['kode_inventaris'] = sprintf('ALT-%s-%04d', $tahun ?: date('Y'), $noUrut ?: ($totalProcessed + 1));
                    $dataPayload['status_kalibrasi'] = 'BELUM DIKALIBRASI';
                    Alkes::create($dataPayload);
                    $created++;
                }

                $totalProcessed++;
            }

            ActivityLog::record(
                'Sinkronisasi Google Sheets',
                "Sinkronisasi berhasil: {$created} data baru ditambahkan, {$updated} data diperbarui.",
                'Pusat Data RS',
                'Sistem Hybrid'
            );
        });
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return [
            'total' => $totalProcessed,
            'created' => $created,
            'updated' => $updated,
            'ruangan_count' => Ruangan::count(),
        ];
    }

    protected function fetchCsv(): string
    {
        // Ekstrak ID spreadsheet
        preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $this->sheetUrl, $matches);
        $sheetId = $matches[1] ?? '1LYrKBO_x7YQmFJ6hS0cbXxvith4z-7vWPBsZ-253_Qo';

        $exportUrls = [
            "https://docs.google.com/spreadsheets/d/{$sheetId}/gviz/tq?tqx=out:csv",
            "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv",
        ];

        foreach ($exportUrls as $url) {
            try {
                $response = Http::timeout(25)
                    ->withHeaders(['User-Agent' => 'ZAPIN-Sync-Agent/2.0'])
                    ->get($url);

                if ($response->successful() && strlen($response->body()) > 100) {
                    return $response->body();
                }
            } catch (\Exception $e) {
                Log::warning("Gagal fetch CSV dari {$url}: " . $e->getMessage());
            }
        }

        return '';
    }

    /**
     * Format payload alkes yang konsisten untuk dikirim ke Google Spreadsheet.
     */
    public function formatAlkesPayload(Alkes $alkes): array
    {
        $rawKib = strtoupper(trim((string)($alkes->kib ?? '')));
        $kib = in_array($rawKib, ['TERDATA', 'TERDAFTAR', 'TERDAFTAR KIB', '1', 'TRUE']) ? 'TERDATA' : 'TIDAK TERDATA';

        $rawAspak = strtoupper(trim((string)($alkes->aspak ?? '')));
        $aspak = in_array($rawAspak, ['TERDATA', 'TERDAFTAR', '1', 'TRUE']) ? 'TERDATA' : 'TIDAK TERDATA';

        return [
            'no_urut' => $alkes->no_urut,
            'nama_barang' => $alkes->nama_barang,
            'merk' => $alkes->merk,
            'tipe' => $alkes->tipe,
            'nomor_seri' => $alkes->nomor_seri,
            'tahun' => $alkes->tahun,
            'jumlah' => $alkes->jumlah,
            'cara_perolehan' => $alkes->cara_perolehan,
            'nilai_perolehan' => $alkes->nilai_perolehan,
            'distributor' => $alkes->distributor,
            'ruangan' => $alkes->ruangan?->nama_ruangan ?? '',
            'lokasi_saat_ini' => $alkes->lokasiRuangan?->nama_ruangan ?? $alkes->lokasi_saat_ini_note ?? '',
            'kondisi' => $alkes->kondisi ?? '',
            'aspak' => $aspak,
            'kib' => $kib,
            'non_kib_dan_aspak' => $alkes->non_kib_dan_aspak ?? '',
            'akl_akd' => $alkes->akl_akd ?? '',
            'keterangan' => $alkes->keterangan ?? '',
        ];
    }

    /**
     * Push pembaruan data alkes dari ZAPIN ke Google Spreadsheet melalui Apps Script Webhook.
     */
    public function pushUpdateToSheet(Alkes $alkes): array
    {
        $webhookUrl = config('zapin.sheet_webhook_url');
        if (empty($webhookUrl)) {
            Log::info("Google Sheet Webhook URL belum diatur (GOOGLE_SHEET_WEBHOOK_URL). Lewati pengiriman ke spreadsheet.");
            return [
                'success' => false,
                'message' => 'GOOGLE_SHEET_WEBHOOK_URL belum dikonfigurasi di .env',
            ];
        }

        $payload = [
            'secret' => config('zapin.api_key'),
            'action' => 'update_row',
            'data' => $this->formatAlkesPayload($alkes),
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ZAPIN-Sync-Engine/2.0',
                ])
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("Push data alkes #{$alkes->no_urut} ({$alkes->nama_barang}) ke Google Sheet berhasil.");
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::warning("Push ke Google Sheet mengembalikan HTTP {$response->status()}: " . $response->body());
            return [
                'success' => false,
                'message' => "HTTP {$response->status()}: " . $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Gagal mengirim update ke Google Sheet: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Push sekumpulan data alkes (batch) sekaligus ke Google Spreadsheet via satu kali HTTP request.
     */
    public function pushBatchUpdateToSheet(array $alkesList): array
    {
        $webhookUrl = config('zapin.sheet_webhook_url');
        if (empty($webhookUrl)) {
            Log::info("Google Sheet Webhook URL belum diatur. Lewati pengiriman batch ke spreadsheet.");
            return [
                'success' => false,
                'message' => 'GOOGLE_SHEET_WEBHOOK_URL belum dikonfigurasi di .env',
            ];
        }

        $items = [];
        foreach ($alkesList as $unit) {
            if ($unit instanceof Alkes) {
                $items[] = $this->formatAlkesPayload($unit->fresh());
            }
        }

        if (empty($items)) {
            return ['success' => true, 'message' => 'Tidak ada item untuk disinkronkan.'];
        }

        $payload = [
            'secret' => config('zapin.api_key'),
            'action' => 'batch_update_rows',
            'items' => $items,
        ];

        try {
            $response = Http::timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ZAPIN-Sync-Engine/2.0',
                ])
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("Push batch " . count($items) . " unit alkes ke Google Sheet berhasil.");
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::warning("Push batch ke Google Sheet HTTP {$response->status()}: " . $response->body());
            return [
                'success' => false,
                'message' => "HTTP {$response->status()}: " . $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Gagal mengirim batch update ke Google Sheet: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Menerima pembaruan data dari Google Spreadsheet via Webhook dan menyimpannya ke PostgreSQL secara atomik.
     */
    public function updateFromSheetWebhook(array $payload): array
    {
        $data = $payload['data'] ?? $payload;
        $noUrut = isset($data['no_urut']) && is_numeric($data['no_urut']) ? (int) $data['no_urut'] : null;

        if ($noUrut === null) {
            return [
                'success' => false,
                'message' => 'Nomor urut (no_urut) tidak ditemukan dalam data webhook.',
            ];
        }

        $alkes = Alkes::withTrashed()->where('no_urut', $noUrut)->first();
        if ($alkes && $alkes->trashed()) {
            $alkes->restore();
        }
        $updateFields = [];

        if (array_key_exists('nama_barang', $data) && !empty(trim((string)$data['nama_barang']))) {
            $updateFields['nama_barang'] = trim((string)$data['nama_barang']);
        }
        if (array_key_exists('merk', $data)) {
            $updateFields['merk'] = trim((string)$data['merk']) ?: null;
        }
        if (array_key_exists('tipe', $data)) {
            $updateFields['tipe'] = trim((string)$data['tipe']) ?: null;
        }
        if (array_key_exists('nomor_seri', $data)) {
            $updateFields['nomor_seri'] = trim((string)$data['nomor_seri']) ?: null;
        }
        if (array_key_exists('tahun', $data)) {
            $updateFields['tahun'] = trim((string)$data['tahun']) ?: null;
        }
        if (array_key_exists('jumlah', $data)) {
            $updateFields['jumlah'] = is_numeric($data['jumlah']) ? (int) $data['jumlah'] : 1;
        }
        if (array_key_exists('cara_perolehan', $data)) {
            $updateFields['cara_perolehan'] = trim((string)$data['cara_perolehan']) ?: null;
        }
        if (array_key_exists('nilai_perolehan', $data)) {
            $updateFields['nilai_perolehan'] = trim((string)$data['nilai_perolehan']) ?: null;
        }
        if (array_key_exists('distributor', $data)) {
            $updateFields['distributor'] = trim((string)$data['distributor']) ?: null;
        }
        if (array_key_exists('ruangan', $data)) {
            $ruanganNama = trim((string)$data['ruangan']);
            if (!empty($ruanganNama)) {
                $ruanganId = $this->getOrCreateRuanganId($ruanganNama);
                $updateFields['ruangan_id'] = $ruanganId;
                if (!$alkes || $alkes->ruangan_id === $alkes->lokasi_ruangan_id) {
                    $updateFields['lokasi_ruangan_id'] = $ruanganId;
                }
            }
        }
        if (array_key_exists('lokasi_saat_ini', $data)) {
            $lokasiRaw = trim((string)$data['lokasi_saat_ini']);
            if (!empty($lokasiRaw)) {
                $lokasiId = $this->getOrCreateRuanganId($lokasiRaw);
                $updateFields['lokasi_ruangan_id'] = $lokasiId;
                $updateFields['lokasi_saat_ini_note'] = $lokasiRaw;
            }
        }
        if (array_key_exists('kondisi', $data)) {
            $kondisiRaw = trim((string)$data['kondisi']);
            $kondisi = !empty($kondisiRaw) ? strtoupper($kondisiRaw) : null;
            $updateFields['kondisi'] = $kondisi;
            if ($kondisi && str_contains($kondisi, 'RUSAK')) {
                $updateFields['status'] = 'Dalam Perbaikan';
            } elseif ($alkes && $alkes->status === 'Dalam Perbaikan') {
                $updateFields['status'] = 'Tersedia';
            }
        }
        if (array_key_exists('aspak', $data)) {
            $rawAspak = strtoupper(trim((string)$data['aspak']));
            $updateFields['aspak'] = in_array($rawAspak, ['TERDATA', 'TERDAFTAR', '1', 'TRUE']) ? 'TERDATA' : 'TIDAK TERDATA';
        }
        if (array_key_exists('kib', $data)) {
            $rawKib = strtoupper(trim((string)$data['kib']));
            $updateFields['kib'] = in_array($rawKib, ['TERDATA', 'TERDAFTAR', 'TERDAFTAR KIB', '1', 'TRUE']) ? 'TERDATA' : 'TIDAK TERDATA';
        }
        if (array_key_exists('non_kib_dan_aspak', $data)) {
            $updateFields['non_kib_dan_aspak'] = trim((string)$data['non_kib_dan_aspak']) ?: null;
        }
        if (array_key_exists('akl_akd', $data)) {
            $updateFields['akl_akd'] = trim((string)$data['akl_akd']) ?: null;
        }
        if (array_key_exists('keterangan', $data)) {
            $updateFields['keterangan'] = trim((string)$data['keterangan']) ?: null;
        }

        if ($alkes) {
            $alkes->update($updateFields);
            $alkes->load('ruangan');

            $colNote = !empty($payload['edited_column_name']) ? " (Kolom: {$payload['edited_column_name']})" : '';
            ActivityLog::record(
                'Sync dari Spreadsheet',
                "Pembaruan otomatis dari Google Spreadsheet{$colNote} untuk alkes '{$alkes->nama_barang}' (No: {$alkes->no_urut}).",
                $alkes->ruangan?->nama_ruangan ?? 'Pusat Data RS',
                'Google Sheets'
            );

            return [
                'success' => true,
                'action' => 'updated',
                'alkes_id' => $alkes->id,
                'nama_barang' => $alkes->nama_barang,
            ];
        } else {
            $namaBarang = $updateFields['nama_barang'] ?? ('Alkes Baru #' . $noUrut);
            $updateFields['no_urut'] = $noUrut;
            $updateFields['nama_barang'] = $namaBarang;
            $updateFields['kode_inventaris'] = sprintf('ALT-%s-%04d', $updateFields['tahun'] ?? date('Y'), $noUrut);
            $updateFields['status_kalibrasi'] = 'BELUM DIKALIBRASI';
            $updateFields['status'] = $updateFields['status'] ?? 'Tersedia';
            if (!isset($updateFields['ruangan_id'])) {
                $updateFields['ruangan_id'] = $this->getOrCreateRuanganId('G. Penunjang');
                $updateFields['lokasi_ruangan_id'] = $updateFields['ruangan_id'];
            }

            $newAlkes = Alkes::create($updateFields);

            ActivityLog::record(
                'Sync dari Spreadsheet',
                "Penambahan unit alkes baru #{$noUrut} ('{$newAlkes->nama_barang}') dari Google Spreadsheet.",
                $newAlkes->ruangan?->nama_ruangan ?? 'Sistem',
                'Google Sheets'
            );

            return [
                'success' => true,
                'action' => 'created',
                'alkes_id' => $newAlkes->id,
                'nama_barang' => $newAlkes->nama_barang,
            ];
        }
    }

    /**
     * Push perintah hapus baris alkes ke Google Spreadsheet melalui Apps Script Webhook.
     */
    public function pushDeleteToSheet(int $noUrut): array
    {
        $webhookUrl = config('zapin.sheet_webhook_url');
        if (empty($webhookUrl)) {
            Log::info("Google Sheet Webhook URL belum diatur. Lewati penghapusan baris di spreadsheet.");
            return [
                'success' => false,
                'message' => 'GOOGLE_SHEET_WEBHOOK_URL belum dikonfigurasi di .env',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ZAPIN-Sync-Engine/2.0',
                ])
                ->post($webhookUrl, [
                    'secret' => config('zapin.api_key'),
                    'action' => 'delete_row',
                    'no_urut' => $noUrut,
                ]);

            if ($response->successful()) {
                Log::info("Push hapus alkes #{$noUrut} ke Google Sheet berhasil.");
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::warning("Push hapus ke Google Sheet mengembalikan HTTP {$response->status()}: " . $response->body());
            return [
                'success' => false,
                'message' => "HTTP {$response->status()}: " . $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Gagal menghapus baris di Google Sheet: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Menyelaraskan alkes di database dengan daftar no_urut aktif dari Google Spreadsheet.
     * Alkes di database yang sudah tidak ada di spreadsheet akan dihapus.
     */
    public function reconcileActiveRows(array $activeNoUruts): array
    {
        if (empty($activeNoUruts)) {
            return [
                'success' => false,
                'message' => 'Daftar active_no_uruts kosong. Rekonsiliasi dibatalkan demi keamanan data.',
            ];
        }

        // Pengaman: Jangan hapus jika data spreadsheet yang dikirim terlalu sedikit dibanding database
        $dbCount = Alkes::count();
        if (count($activeNoUruts) < 100 && $dbCount > 200) {
            return [
                'success' => false,
                'message' => 'Jumlah baris aktif di spreadsheet terlalu sedikit. Rekonsiliasi dibatalkan demi keamanan.',
            ];
        }

        $missingAlkes = Alkes::whereNotIn('no_urut', $activeNoUruts)->get();
        $deletedCount = 0;
        $deletedNames = [];

        foreach ($missingAlkes as $alkes) {
            $name = "{$alkes->nama_barang} (#{$alkes->no_urut})";
            $deletedNames[] = $name;

            ActivityLog::record(
                'Sync Hapus dari Spreadsheet',
                "Alkes '{$alkes->nama_barang}' (No: {$alkes->no_urut}) dihapus otomatis karena barisnya telah dihapus di Google Spreadsheet.",
                $alkes->ruangan?->nama_ruangan ?? 'Pusat Data RS',
                'Google Sheets'
            );

            $alkes->delete();
            $deletedCount++;
        }

        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'deleted_items' => $deletedNames,
            'message' => "{$deletedCount} data alkes diselaraskan dan dihapus dari sistem ZAPIN.",
        ];
    }
}
