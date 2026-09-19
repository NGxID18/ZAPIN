<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\MutasiAlkes;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiAlkesController extends Controller
{
    public function index(Request $request)
    {
        $query = MutasiAlkes::with(['alkes.ruangan', 'ruanganAsal', 'ruanganTujuan']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('alasan_mutasi', 'ilike', "%{$s}%")
                  ->orWhere('pemohon', 'ilike', "%{$s}%")
                  ->orWhere('penanggung_jawab', 'ilike', "%{$s}%")
                  ->orWhereHas('alkes', function ($aq) use ($s) {
                      $aq->where('nama_barang', 'ilike', "%{$s}%")
                         ->orWhere('nomor_seri', 'ilike', "%{$s}%");
                  });
            });
        }

        if ($request->filled('ruangan_asal_id')) {
            $query->where('ruangan_asal_id', $request->ruangan_asal_id);
        }

        if ($request->filled('ruangan_tujuan_id')) {
            $query->where('ruangan_tujuan_id', $request->ruangan_tujuan_id);
        }

        $isAll = $request->per_page === 'all';
        $perPage = $isAll ? max(1, (clone $query)->count()) : min(max((int) $request->get('per_page', 50), 1), 500);
        $mutasiList = $query->latest()->paginate($perPage)->withQueryString();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $totalDipindahkan = Alkes::whereColumn('ruangan_id', '!=', 'lokasi_ruangan_id')->count();

        return view('mutasi.index', compact('mutasiList', 'ruanganList', 'totalDipindahkan'));
    }

    public function create(Request $request)
    {
        $selectedAlkesId = $request->query('alkes_id');
        $alkesList = Alkes::with(['ruangan', 'lokasiRuangan'])->accessibleByCurrentRole()->orderBy('nama_barang', 'asc')->get();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();

        return view('mutasi.create', compact('alkesList', 'ruanganList', 'selectedAlkesId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alkes_id' => 'required|exists:alkes,id',
            'ruangan_tujuan_id' => 'required|exists:ruangan,id',
            'pemohon' => 'required|string|max:255',
            'penanggung_jawab' => 'required|string|max:255',
            'alasan_mutasi' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            $alkes = Alkes::where('id', $validated['alkes_id'])->lockForUpdate()->firstOrFail();
            $ruanganAsalId = $alkes->lokasi_ruangan_id ?? $alkes->ruangan_id;

            $mutasi = MutasiAlkes::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $ruanganAsalId,
                'ruangan_tujuan_id' => $validated['ruangan_tujuan_id'],
                'pemohon' => $validated['pemohon'],
                'penanggung_jawab' => $validated['penanggung_jawab'],
                'alasan_mutasi' => $validated['alasan_mutasi'],
                'tanggal_mutasi' => now(),
                'status' => 'Selesai',
            ]);

            $ruanganTujuan = Ruangan::find($validated['ruangan_tujuan_id']);

            $alkes->update([
                'lokasi_ruangan_id' => $validated['ruangan_tujuan_id'],
                'lokasi_saat_ini_note' => "Dipindahkan ke {$ruanganTujuan->nama_ruangan}",
            ]);

            ActivityLog::record(
                'Mutasi Ruangan',
                "Alat '{$alkes->nama_barang}' (SN: " . ($alkes->nomor_seri ?: '-') . ") dipindahkan ke {$ruanganTujuan->nama_ruangan} oleh {$validated['pemohon']}.",
                $ruanganTujuan->nama_ruangan
            );
        });

        return redirect()->route('mutasi.index')->with('success', 'Mutasi perpindahan alat berhasil dicatat ke database.');
    }
}
