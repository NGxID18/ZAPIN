<?php

namespace Database\Seeders;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use App\Models\Alkes;
use App\Models\Nomenklatur;
use App\Models\Ruangan;
use Illuminate\Database\Seeder;

class ZapinSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Primary Nomenklatur Standard
        $defaultNom = Nomenklatur::updateOrCreate(
            ['kode_nomenklatur' => 'NOM-STD-01'],
            ['nama_alat' => 'Peralatan Medis RSJKO EHD', 'kategori' => 'General Medical']
        );

        // 2. Load Structured CSV Source (database/csv_source/Data Alkes 2026 Terstruktur.csv)
        $csvFile = base_path('database/csv_source/Data Alkes 2026 Terstruktur.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("File CSV sumber 'database/csv_source/Data Alkes 2026 Terstruktur.csv' tidak ditemukan!");
            return;
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            return;
        }

        // Skip Header: No,Nama Barang,Merk,Tipe,Seri Number,Tahun,Ruang Pemilik,Lokasi Fisik saat Ini,Kondisi,Status Kalibrasi,Tanggal Kalibrasi Terakhir,Keterangan
        fgetcsv($handle);

        $ruanganCache = [];

        $getOrCreateRuangan = function ($namaRuangan) use (&$ruanganCache) {
            $nameClean = trim($namaRuangan);
            if (empty($nameClean)) {
                $nameClean = 'CSSD';
            }
            $key = strtolower($nameClean);

            if (isset($ruanganCache[$key])) {
                return $ruanganCache[$key];
            }

            $kodeStr = 'R-' . strtoupper(str_replace([' ', '/'], '-', $nameClean));
            $ruangan = Ruangan::firstOrCreate(
                ['nama_ruangan' => $nameClean],
                ['kode_ruangan' => $kodeStr]
            );

            $ruanganCache[$key] = $ruangan;
            return $ruangan;
        };

        $count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 10) {
                continue;
            }

            $rawNo = trim($data[0]);
            $namaBarang = trim($data[1]);
            $merk = trim($data[2]);
            $tipe = trim($data[3]);
            $nomorSeri = trim($data[4]);
            $tahunPengadaan = trim($data[5]);
            $ruangPemilikNama = trim($data[6]);
            $lokasiFisikNama = trim($data[7]);
            $rawKondisi = trim($data[8]);
            $statusKalibrasi = trim($data[9]);
            $rawTglKalibrasi = trim($data[10] ?? '');
            $keterangan = trim($data[11] ?? '');

            if (empty($namaBarang)) {
                continue;
            }

            $ruanganPemilik = $getOrCreateRuangan($ruangPemilikNama);
            $ruanganLokasi = $getOrCreateRuangan($lokasiFisikNama);

            // Kondisi Mapping
            $kondisiEnum = match ($rawKondisi) {
                'Rusak Ringan' => KondisiAlkes::RUSAK_RINGAN,
                'Rusak Berat' => KondisiAlkes::RUSAK_BERAT,
                default => KondisiAlkes::BAIK,
            };

            // Status Mapping
            if ($kondisiEnum !== KondisiAlkes::BAIK || strtoupper($lokasiFisikNama) === 'IPSRS') {
                $statusEnum = StatusAlkes::DALAM_PERBAIKAN;
            } else {
                $statusEnum = StatusAlkes::TERSEDIA;
            }

            // Tanggal Kalibrasi (null if 'Belum ada data')
            $tglTerakhir = ($rawTglKalibrasi !== '' && $rawTglKalibrasi !== 'Belum ada data') ? $rawTglKalibrasi : null;

            $invCode = 'ALT-2026-' . str_pad($rawNo, 4, '0', STR_PAD_LEFT);

            Alkes::updateOrCreate(
                ['kode_inventaris' => $invCode],
                [
                    'nama_barang' => $namaBarang,
                    'nomenklatur_id' => $defaultNom->id,
                    'merk' => ($merk !== '' && $merk !== '-') ? $merk : null,
                    'tipe' => ($tipe !== '' && $tipe !== '-') ? $tipe : null,
                    'nomor_seri' => ($nomorSeri !== '' && $nomorSeri !== '-') ? $nomorSeri : null,
                    'tahun_pengadaan' => ($tahunPengadaan !== '' && $tahunPengadaan !== '-') ? $tahunPengadaan : null,
                    'jumlah' => 1,
                    'cara_perolehan' => null,
                    'nilai_perolehan' => 0,
                    'ruangan_id' => $ruanganPemilik->id,
                    'lokasi_ruangan_id' => $ruanganLokasi->id,
                    'lokasi_saat_ini_note' => null,
                    'status' => $statusEnum->value,
                    'kondisi' => $kondisiEnum->value,
                    'status_kalibrasi' => (!empty($statusKalibrasi)) ? $statusKalibrasi : 'BELUM DIKALIBRASI',
                    'aspak_status' => 'TERDATA',
                    'kib_status' => false,
                    'tanggal_kalibrasi_terakhir' => $tglTerakhir,
                    'tanggal_kalibrasi_berikutnya' => null,
                    'keterangan' => $keterangan !== '' ? $keterangan : null,
                ]
            );

            $count++;
        }

        fclose($handle);

        $this->command->info("BERHASIL IMPOR! Total {$count} unit alkes terstruktur dimasukkan ke database.");
    }
}
