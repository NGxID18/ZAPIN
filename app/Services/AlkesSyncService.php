<?php

namespace App\Services;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\Ruangan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AlkesSyncService
{
    public function sync(array $items)
    {
        $updatedCount = 0;
        $createdCount = 0;
        $savedResults = [];

        DB::transaction(function () use ($items, &$updatedCount, &$createdCount, &$savedResults) {
            // Helper to get or create Ruangan
            $ruanganCache = [];
            $getRuanganId = function ($namaRuangan) use (&$ruanganCache) {
                $nameClean = trim($namaRuangan ?? '');
                if (empty($nameClean) || $nameClean === '-' || in_array(strtolower($nameClean), ['ruang pemilik', 'lokasi alkes', 'lokasi fisik', 'lokasi fisik saat ini', 'ruangan'])) {
                    $nameClean = 'CSSD';
                }
                $key = strtolower($nameClean);
                if (isset($ruanganCache[$key])) {
                    return $ruanganCache[$key];
                }

                $ruangan = Ruangan::whereRaw('LOWER(nama_ruangan) = ?', [$key])->first();
                if (!$ruangan) {
                    $kodeStr = 'R-' . strtoupper(substr(str_replace([' ', '/'], '-', $nameClean), 0, 10));
                    $ruangan = Ruangan::create([
                        'nama_ruangan' => $nameClean,
                        'kode_ruangan' => $kodeStr,
                    ]);
                    Cache::forget('ruangan_list');
                }
                $ruanganCache[$key] = $ruangan->id;
                return $ruangan->id;
            };

            // Kondisi Mapping Helper
            $mapKondisi = function ($rawKondisi) {
                $clean = strtolower(trim($rawKondisi ?? ''));
                if (str_contains($clean, 'rusak berat')) return KondisiAlkes::RUSAK_BERAT->value;
                if (str_contains($clean, 'rusak ringan')) return KondisiAlkes::RUSAK_RINGAN->value;
                return KondisiAlkes::BAIK->value;
            };

            foreach ($items as $row) {
                $dbId = !empty($row['id']) ? (int) $row['id'] : null;
                $namaBarang = trim($row['nama_barang'] ?? '');
                if (empty($namaBarang) || strtolower($namaBarang) === 'nama barang' || str_starts_with(strtolower($namaBarang), 'nama barang') || str_contains(strtolower($namaBarang), 'ketik data') || str_contains(strtolower($namaBarang), 'form tambah')) {
                    continue;
                }

                $ruangPemilikId = $getRuanganId($row['ruang_pemilik'] ?? null);
                $lokasiVal = !empty($row['lokasi_alkes']) ? $row['lokasi_alkes'] : ($row['lokasi_fisik'] ?? null);
                $lokasiFisikId = !empty($lokasiVal) ? $getRuanganId($lokasiVal) : $ruangPemilikId;
                $kondisi = $mapKondisi($row['kondisi'] ?? 'Baik');
                $status = ($kondisi === KondisiAlkes::BAIK->value) ? StatusAlkes::TERSEDIA->value : StatusAlkes::DALAM_PERBAIKAN->value;

                $alkes = null;
                if ($dbId) {
                    $alkes = Alkes::find($dbId);
                }

                if (!$alkes && !empty($row['seri_number']) && $row['seri_number'] !== '-') {
                    $alkes = Alkes::where('nomor_seri', trim($row['seri_number']))->first();
                }

                if ($alkes) {
                    $newMerk = !empty($row['merk']) && $row['merk'] !== '-' ? trim($row['merk']) : $alkes->merk;
                    $newTipe = !empty($row['tipe']) && $row['tipe'] !== '-' ? trim($row['tipe']) : $alkes->tipe;
                    $newSN = !empty($row['seri_number']) && $row['seri_number'] !== '-' ? trim($row['seri_number']) : $alkes->nomor_seri;
                    $newTahun = !empty($row['tahun']) && $row['tahun'] !== '-' ? trim($row['tahun']) : $alkes->tahun_pengadaan;
                    $newKalibrasiStatus = !empty($row['status_kalibrasi']) && $row['status_kalibrasi'] !== '-' ? trim($row['status_kalibrasi']) : $alkes->status_kalibrasi;
                    $newKet = !empty($row['keterangan']) && $row['keterangan'] !== '-' ? trim($row['keterangan']) : $alkes->keterangan;
                    $currentKondisiVal = $alkes->kondisi instanceof \App\Enums\KondisiAlkes ? $alkes->kondisi->value : (string) $alkes->kondisi;

                    $isChanged = (
                        $alkes->nama_barang !== $namaBarang ||
                        $alkes->merk !== $newMerk ||
                        $alkes->tipe !== $newTipe ||
                        $alkes->nomor_seri !== $newSN ||
                        $alkes->tahun_pengadaan !== $newTahun ||
                        $alkes->ruangan_id != $ruangPemilikId ||
                        $alkes->lokasi_ruangan_id != $lokasiFisikId ||
                        $currentKondisiVal !== $kondisi ||
                        $alkes->status_kalibrasi !== $newKalibrasiStatus ||
                        $alkes->keterangan !== $newKet
                    );

                    if ($isChanged) {
                        $alkes->update([
                            'nama_barang' => $namaBarang,
                            'merk' => $newMerk,
                            'tipe' => $newTipe,
                            'nomor_seri' => $newSN,
                            'tahun_pengadaan' => $newTahun,
                            'ruangan_id' => $ruangPemilikId,
                            'lokasi_ruangan_id' => $lokasiFisikId,
                            'kondisi' => $kondisi,
                            'status' => $status,
                            'status_kalibrasi' => $newKalibrasiStatus,
                            'keterangan' => $newKet,
                        ]);

                        $updatedCount++;
                        $savedResults[] = [
                            'no' => $alkes->id,
                            'status' => 'updated',
                            'nama_barang' => $alkes->nama_barang,
                        ];
                    }
                } else {
                    $kodeInventaris = Alkes::generateKodeInventaris();

                    $statusKalibrasi = !empty($row['status_kalibrasi']) && $row['status_kalibrasi'] !== '-' ? trim($row['status_kalibrasi']) : 'BELUM DIKALIBRASI';
                    $tglKalibrasi = null;
                    if (!empty($row['tanggal_kalibrasi_terakhir']) && $row['tanggal_kalibrasi_terakhir'] !== '-' && $row['tanggal_kalibrasi_terakhir'] !== 'Belum ada data') {
                        try {
                            $tglKalibrasi = \Carbon\Carbon::parse($row['tanggal_kalibrasi_terakhir'])->toDateString();
                        } catch (\Exception $e) {
                            $tglKalibrasi = null;
                        }
                    }

                    $newAlkes = Alkes::create([
                        'kode_inventaris' => $kodeInventaris,
                        'nama_barang' => $namaBarang,
                        'nomenklatur_id' => null,
                        'merk' => !empty($row['merk']) && $row['merk'] !== '-' ? trim($row['merk']) : null,
                        'tipe' => !empty($row['tipe']) && $row['tipe'] !== '-' ? trim($row['tipe']) : null,
                        'nomor_seri' => !empty($row['seri_number']) && $row['seri_number'] !== '-' ? trim($row['seri_number']) : null,
                        'tahun_pengadaan' => !empty($row['tahun']) && $row['tahun'] !== '-' ? trim($row['tahun']) : date('Y'),
                        'jumlah' => 1,
                        'ruangan_id' => $ruangPemilikId,
                        'lokasi_ruangan_id' => $lokasiFisikId,
                        'kondisi' => $kondisi,
                        'status' => $status,
                        'status_kalibrasi' => $statusKalibrasi,
                        'tanggal_kalibrasi_terakhir' => $tglKalibrasi,
                        'keterangan' => !empty($row['keterangan']) && $row['keterangan'] !== '-' ? trim($row['keterangan']) : null,
                    ]);

                    $createdCount++;
                    $savedResults[] = [
                        'no' => $newAlkes->id,
                        'status' => 'created',
                        'nama_barang' => $newAlkes->nama_barang,
                    ];
                }
            }

            if ($createdCount > 0 || $updatedCount > 0) {
                ActivityLog::record(
                    'Sinkronisasi Spreadsheet',
                    "Sinkronisasi Google Spreadsheet: {$createdCount} alkes baru ditambahkan, {$updatedCount} data diperbarui.",
                    'Google Sheets'
                );
            }
        });

        return [
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'results' => $savedResults,
        ];
    }
}
