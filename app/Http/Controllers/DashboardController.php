<?php

namespace App\Http\Controllers;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
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

        // Exact Condition Breakdown
        $alkesBaik = Alkes::where('kondisi', KondisiAlkes::BAIK->value)->count();
        $alkesRusak = Alkes::where('kondisi', '!=', KondisiAlkes::BAIK->value)->count();
        $alkesTersedia = $alkesBaik;

        // Ruangan List based on Physical Location (Lokasi Fisik saat Ini)
        $ruanganList = Ruangan::withCount(['alkesLokasi as alkes_count', 'alkesLokasi as alkes_rusak_count' => function ($q) {
            $q->where('kondisi', '!=', KondisiAlkes::BAIK->value);
        }])->orderBy('nama_ruangan', 'asc')->get();

        $mutasiTerbaru = MutasiAlkes::with(['alkes', 'ruanganAsal', 'ruanganTujuan'])
            ->latest()
            ->take(6)
            ->get();

        $logPerbaikanTerbaru = LogPemeliharaan::with(['alkes.ruangan'])
            ->latest()
            ->take(5)
            ->get();

        $chartStatusData = [
            'Kondisi Baik' => $alkesBaik,
            'Rusak / Perlu Perbaikan' => $alkesRusak,
        ];

        $chartRuanganLabels = [];
        $chartKondisiBaik = [];
        $chartKondisiRusak = [];

        foreach ($ruanganList as $ruang) {
            if ($ruang->alkes_count > 0) {
                $chartRuanganLabels[] = $ruang->nama_ruangan;
                $rusakCount = (int) $ruang->alkes_rusak_count;
                $baikCount = max(0, ((int) $ruang->alkes_count) - $rusakCount);
                $chartKondisiBaik[] = $baikCount;
                $chartKondisiRusak[] = $rusakCount;
            }
        }

        $recentActivityLogs = ActivityLog::latest()->take(6)->get();

        return view('dashboard', compact(
            'totalAlkes',
            'alkesTersedia',
            'alkesRusak',
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
}
