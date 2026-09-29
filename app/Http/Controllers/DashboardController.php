<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\LogPemeliharaan;
use App\Models\MutasiAlkes;
use App\Models\Ruangan;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAlkes = Alkes::count();
        $alkesTersedia = Alkes::where('kondisi', 'BAIK')->count();
        $alkesRusak = Alkes::where('kondisi', 'LIKE', '%RUSAK%')->count();

        $ruanganList = Ruangan::withCount([
            'alkesLokasi as alkes_count',
            'alkesLokasi as alkes_rusak_count' => function ($q) {
                $q->where('kondisi', 'LIKE', '%RUSAK%');
            }
        ])->orderBy('nama_ruangan', 'asc')->get();

        $mutasiTerbaru = MutasiAlkes::with(['alkes', 'ruanganAsal', 'ruanganTujuan'])->latest()->take(5)->get();
        $logPerbaikanTerbaru = LogPemeliharaan::with(['alkes.ruangan'])->latest()->take(5)->get();
        $recentActivityLogs = ActivityLog::latest()->take(6)->get();

        $chartStatusData = [
            'Kondisi Baik' => $alkesTersedia,
            'Rusak / Perlu Perbaikan' => $alkesRusak,
        ];

        $chartRuanganLabels = [];
        $chartKondisiBaik = [];
        $chartKondisiRusak = [];

        foreach ($ruanganList as $ruang) {
            if ($ruang->alkes_count > 0) {
                $chartRuanganLabels[] = $ruang->nama_ruangan;
                $rusak = (int) $ruang->alkes_rusak_count;
                $baik = max(0, ((int) $ruang->alkes_count) - $rusak);
                $chartKondisiBaik[] = $baik;
                $chartKondisiRusak[] = $rusak;
            }
        }

        $allAlkes = Alkes::with('ruangan')->select('id', 'no_urut', 'nama_barang', 'nilai_perolehan', 'cara_perolehan', 'kondisi', 'ruangan_id')->get();
        $totalNilaiAset = 0.0;
        $nilaiBaik = 0.0;
        $nilaiRusak = 0.0;
        $unitBernilai = 0;

        $sumberStats = [
            'DAK' => [
                'label' => 'DAK (Dana Alokasi Khusus)',
                'badge' => 'DAK',
                'color' => '#6366f1',
                'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'bar_bg' => 'bg-indigo-600',
                'icon' => 'ri-government-line',
                'value' => 0.0,
                'count' => 0,
                'query' => 'DAK',
            ],
            'APBN' => [
                'label' => 'APBN / Hibah Kemenkes',
                'badge' => 'APBN / Hibah',
                'color' => '#0d9488',
                'bg' => 'bg-teal-50 text-teal-700 border-teal-200',
                'bar_bg' => 'bg-teal-600',
                'icon' => 'ri-gift-line',
                'value' => 0.0,
                'count' => 0,
                'query' => 'HIBAH',
            ],
            'MUTASI' => [
                'label' => 'Mutasi Antar Unit / Dinkes',
                'badge' => 'Mutasi',
                'color' => '#0284c7',
                'bg' => 'bg-sky-50 text-sky-700 border-sky-200',
                'bar_bg' => 'bg-sky-600',
                'icon' => 'ri-arrow-left-right-line',
                'value' => 0.0,
                'count' => 0,
                'query' => 'MUTASI',
            ],
            'APBD' => [
                'label' => 'APBD Provinsi Kepri',
                'badge' => 'APBD',
                'color' => '#059669',
                'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'bar_bg' => 'bg-emerald-600',
                'icon' => 'ri-building-line',
                'value' => 0.0,
                'count' => 0,
                'query' => 'APBD',
            ],
            'BLUD' => [
                'label' => 'BLUD Operasional RS',
                'badge' => 'BLUD',
                'color' => '#d97706',
                'bg' => 'bg-amber-50 text-amber-700 border-amber-200',
                'bar_bg' => 'bg-amber-600',
                'icon' => 'ri-money-dollar-circle-line',
                'value' => 0.0,
                'count' => 0,
                'query' => 'BLUD',
            ],
            'LAINNYA' => [
                'label' => 'Lainnya / Belum Tercatat',
                'badge' => 'Lainnya',
                'color' => '#94a3b8',
                'bg' => 'bg-slate-100 text-slate-700 border-slate-200',
                'bar_bg' => 'bg-slate-400',
                'icon' => 'ri-question-line',
                'value' => 0.0,
                'count' => 0,
                'query' => '',
            ],
        ];

        $parsedList = [];
        foreach ($allAlkes as $a) {
            $val = $this->parseRupiah($a->nilai_perolehan);
            if ($val > 0) {
                $totalNilaiAset += $val;
                $unitBernilai++;
                if (strtoupper($a->kondisi ?? '') === 'BAIK') {
                    $nilaiBaik += $val;
                } else {
                    $nilaiRusak += $val;
                }
                $a->numeric_value = $val;
                $parsedList[] = $a;
            }

            $cp = strtoupper(trim($a->cara_perolehan ?? ''));
            $key = 'LAINNYA';
            if (str_contains($cp, 'DAK')) $key = 'DAK';
            elseif (str_contains($cp, 'HIBAH') || str_contains($cp, 'APBN')) $key = 'APBN';
            elseif (str_contains($cp, 'MUTASI')) $key = 'MUTASI';
            elseif (str_contains($cp, 'APBD')) $key = 'APBD';
            elseif (str_contains($cp, 'BLUD')) $key = 'BLUD';

            $sumberStats[$key]['value'] += $val;
            $sumberStats[$key]['count']++;
        }

        foreach ($sumberStats as $k => &$s) {
            $s['persen'] = $totalNilaiAset > 0 ? round(($s['value'] / $totalNilaiAset) * 100, 1) : 0;
            $s['formatted_value'] = $this->formatCompactRupiah($s['value']);
            $s['full_value'] = 'Rp ' . number_format($s['value'], 0, ',', '.');
        }
        unset($s);

        uasort($sumberStats, fn($a, $b) => $b['value'] <=> $a['value']);

        usort($parsedList, fn($a, $b) => $b->numeric_value <=> $a->numeric_value);
        $topValuedAlkes = array_slice($parsedList, 0, 5);

        $totalNilaiAsetFormatted = $this->formatCompactRupiah($totalNilaiAset);
        $totalNilaiAsetFull = 'Rp ' . number_format($totalNilaiAset, 0, ',', '.');
        $nilaiBaikFormatted = $this->formatCompactRupiah($nilaiBaik);
        $nilaiRusakFormatted = $this->formatCompactRupiah($nilaiRusak);
        $nilaiBaikPersen = $totalNilaiAset > 0 ? round(($nilaiBaik / $totalNilaiAset) * 100, 1) : 0;
        $nilaiRusakPersen = $totalNilaiAset > 0 ? round(($nilaiRusak / $totalNilaiAset) * 100, 1) : 0;

        return view('dashboard', compact(
            'totalAlkes',
            'alkesTersedia',
            'alkesRusak',
            'totalNilaiAset',
            'totalNilaiAsetFormatted',
            'totalNilaiAsetFull',
            'nilaiBaik',
            'nilaiBaikFormatted',
            'nilaiBaikPersen',
            'nilaiRusak',
            'nilaiRusakFormatted',
            'nilaiRusakPersen',
            'unitBernilai',
            'sumberStats',
            'topValuedAlkes',
            'ruanganList',
            'mutasiTerbaru',
            'logPerbaikanTerbaru',
            'chartStatusData',
            'chartRuanganLabels',
            'chartKondisiBaik',
            'chartKondisiRusak',
            'recentActivityLogs'
        ));
    }

    protected function parseRupiah(?string $raw): float
    {
        if (empty($raw)) return 0.0;
        $clean = preg_replace('/[^0-9,]/', '', $raw);
        if (str_contains($clean, ',')) {
            $parts = explode(',', $clean);
            $clean = $parts[0];
        }
        return (float) $clean;
    }

    protected function formatCompactRupiah(float $amount): string
    {
        if ($amount >= 1000000000) {
            return 'Rp ' . number_format($amount / 1000000000, 2, ',', '.') . ' M';
        }
        if ($amount >= 1000000) {
            return 'Rp ' . number_format($amount / 1000000, 2, ',', '.') . ' Jt';
        }
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
