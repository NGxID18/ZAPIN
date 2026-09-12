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
            $escaped = addcslashes(trim($request->search), '%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('alasan_mutasi', 'like', "%{$escaped}%")
                  ->orWhere('pemohon', 'like', "%{$escaped}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$escaped}%")
                  ->orWhereHas('alkes', function ($aq) use ($escaped) {
                      $aq->where('nama_barang', 'like', "%{$escaped}%")
                         ->orWhere('nomor_seri', 'like', "%{$escaped}%");
                  });
            });
        }

        if ($request->filled('ruangan_asal_id')) {
            $query->where('ruangan_asal_id', $request->ruangan_asal_id);
        }

        if ($request->filled('ruangan_tujuan_id')) {
            $query->where('ruangan_tujuan_id', $request->ruangan_tujuan_id);
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $mutasiList = $query->latest()->paginate($perPage)->withQueryString();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();

        return view('mutasi.index', compact('mutasiList', 'ruanganList'));
    }

    public function create(Request $request)
    {
        $selectedAlkesId = $request->query('alkes_id');
        $alkesList = Alkes::with(['ruangan', 'lokasiRuangan'])->accessibleByCurrentRole()->orderBy('nama_barang', 'asc')->get();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();

        return view('mutasi.create', compact('alkesList', 'ruanganList', 'selectedAlkesId'));
    }

    public function store(\App\Http\Requests\StoreMutasiRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $alkes = Alkes::where('id', $validated['alkes_id'])->lockForUpdate()->firstOrFail();
            $ruanganAsalId = $alkes->lokasi_ruangan_id ?? $alkes->ruangan_id;

            // Validasi hak akses: Peran ruangan hanya boleh memutasikan alat di ruangannya
            if (session('user_role') === 'ruangan' && session('user_ruangan_id')) {
                $userRuanganId = (int) session('user_ruangan_id');
                if ($alkes->ruangan_id !== $userRuanganId && $alkes->lokasi_ruangan_id !== $userRuanganId) {
                    abort(403, 'Akses Ditolak: Anda hanya berwenang memindahkan alat kesehatan milik atau yang berada di ruangan Anda.');
                }
            }

            if ($alkes->status === \App\Enums\StatusAlkes::DIPINJAM) {
                abort(422, 'Alat sedang dalam status dipinjam. Harap kembalikan terlebih dahulu sebelum memindahkan lokasi.');
            }

            if ($ruanganAsalId == $validated['ruangan_tujuan_id']) {
                abort(422, 'Ruangan tujuan harus berbeda dari ruangan asal fisik saat ini!');
            }

            $mutasi = MutasiAlkes::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $ruanganAsalId,
                'ruangan_tujuan_id' => $validated['ruangan_tujuan_id'],
                'tanggal_mutasi' => now(),
                'pemohon' => $validated['pemohon'],
                'penanggung_jawab' => $validated['penanggung_jawab'],
                'alasan_mutasi' => $validated['alasan_mutasi'],
                'status_persetujuan' => 'Disetujui',
            ]);

            $alkes->update([
                'lokasi_ruangan_id' => $validated['ruangan_tujuan_id'],
            ]);

            $mutasi->load(['ruanganAsal', 'ruanganTujuan']);
            $rAsal = $mutasi->ruanganAsal->nama_ruangan ?? 'Ruangan Asal';
            $rTujuan = $mutasi->ruanganTujuan->nama_ruangan ?? 'Ruangan Tujuan';

            ActivityLog::record(
                'Pindah Ruangan Alkes',
                "Memindahkan lokasi fisik unit '{$alkes->nama_barang}' ({$alkes->kode_inventaris}) dari {$rAsal} ke {$rTujuan}.",
                $rTujuan
            );
            
            $request->session()->flash('mutasi_tujuan', $rTujuan);
        });

        return redirect()->route('mutasi.index')
            ->with('success', "Proses pemindahan lokasi unit alkes ke " . session('mutasi_tujuan') . " berhasil!");
    }
}
