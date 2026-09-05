<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;

class RuanganController extends Controller
{
    public function index()
    {
        $ruanganList = Ruangan::withCount([
            'alkesLokasi as alkes_count',
            'alkesLokasi as alkes_baik_count' => function ($q) {
                $q->where('kondisi', 'baik');
            },
            'alkesLokasi as alkes_rusak_count' => function ($q) {
                $q->where('kondisi', '!=', 'baik');
            }
        ])
        ->orderBy('nama_ruangan', 'asc')
        ->get();

        $totalRuangan = $ruanganList->count();

        return view('ruangan.index', compact('ruanganList', 'totalRuangan'));
    }
}
