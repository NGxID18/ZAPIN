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

    public function __construct(?string $url = null)
    {
        $this->sheetUrl = $url ?? config('zapin.google_sheet_url', env('GOOGLE_SHEET_URL', ''));
    }

    public function sync(): array
    {
        $csvContent = $this->fetchCsv();

        if (empty($csvContent)) {
            throw new \RuntimeException('Gagal mengunduh data CSV dari Google Spreadsheet. Pastikan tautan spreadsheet bersifat publik (view access).');
        }

        $lines = explode("\n", $csvContent);
        if (count($lines) < 2) {
            throw new \RuntimeException('Data CSV Google Spreadsheet kosong atau tidak memiliki baris data.');
        }

        $created = 0;
        $updated = 0;
        $totalProcessed = 0;

        DB::transaction(function () use ($lines, &$created, &$updated, &$totalProcessed) {
            $header = null;
            $roomCache = [];

            $getOrCreateRuanganId = function (?string $rawNama) use (&$roomCache): int {
                $nama = trim($rawNama ?? '');
                if (empty($nama) || $nama === '-') {
                    $nama = 'G. Penunjang';
                }

                $key = strtolower($nama);
                if (isset($roomCache[$key])) {
                    return $roomCache[$key];
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

                $roomCache[$key] = $ruangan->id;
                return $ruangan->id;
            };

            foreach ($lines as $lineIndex => $line) {
                if (trim($line) === '') {
                    continue;
                }

                $row = str_getcsv($line);
                if ($lineIndex === 0) {
                    $header = $row;
                    continue;
                }

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

                $ruanganId = $getOrCreateRuanganId($ruanganNama);

                // Cari record alkes yang cocok: prioritas utama berdasarkan no_urut spreadsheet (1..638)
                $alkes = null;
                if ($noUrut !== null) {
                    $alkes = Alkes::where('no_urut', $noUrut)->first();
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
}
