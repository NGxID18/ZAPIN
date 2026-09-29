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

        // 25 Ruangan Riil dengan kalkulasi alkes aktual
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
