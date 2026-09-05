<?php

namespace App\Http\Controllers;

use App\Enums\StatusAlkes;
use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\PeminjamanAlkes;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanAlkesController extends Controller
{
    public function index(Request $request)
    {
        $query = PeminjamanAlkes::with(['alkes.ruangan', 'ruanganPeminjam']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('peminjam_nama', 'like', "%{$search}%")
                  ->orWhereHas('alkes', function ($aq) use ($search) {
                      $aq->where('nama_barang', 'like', "%{$search}%")
                         ->orWhere('nomor_seri', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $peminjamanList = $query->latest()->paginate($perPage)->withQueryString();
        $ruanganList = \Illuminate\Support\Facades\Cache::remember('ruangan_list', 86400, fn() => Ruangan::orderBy('nama_ruangan', 'asc')->get());

        return view('peminjaman.index', compact('peminjamanList', 'ruanganList'));
    }

    public function store(\App\Http\Requests\StorePeminjamanRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $alkes = Alkes::where('id', $validated['alkes_id'])->lockForUpdate()->firstOrFail();
            
            if ($alkes->status->value !== StatusAlkes::TERSEDIA->value) {
                abort(422, 'Alat ini sedang tidak tersedia untuk dipinjam.');
            }

            $ruanganPeminjam = Ruangan::findOrFail($validated['ruangan_peminjam_id']);

            PeminjamanAlkes::create([
                'alkes_id' => $alkes->id,
                'ruangan_peminjam_id' => $validated['ruangan_peminjam_id'],
                'peminjam_nama' => $validated['peminjam_nama'],
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'estimasi_kembali' => $validated['estimasi_kembali'],
                'status' => 'Dipinjam',
                'keterangan' => $validated['keterangan'],
            ]);

            $alkes->update([
                'status' => StatusAlkes::DIPINJAM->value,
                'lokasi_saat_ini_note' => 'Dipinjam oleh ' . $validated['peminjam_nama'] . ' - ' . $ruanganPeminjam->nama_ruangan,
            ]);

            ActivityLog::record(
                'Peminjaman Alat',
                "Alat '{$alkes->nama_barang}' (SN: {$alkes->nomor_seri}) dipinjam oleh {$validated['peminjam_nama']} ({$ruanganPeminjam->nama_ruangan})",
                session('user_role_label', 'Petugas Ruangan')
            );
        });

        return redirect()->route('peminjaman.index')->with('success', 'Peminjaman alat berhasil dicatat.');
    }

    public function kembalikan(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $peminjaman = PeminjamanAlkes::findOrFail($id);
            $alkes = Alkes::findOrFail($peminjaman->alkes_id);

            $peminjaman->update([
                'status' => 'Dikembalikan',
                'tanggal_dikembalikan' => now(),
            ]);

            $alkes->update([
                'status' => StatusAlkes::TERSEDIA->value,
                'lokasi_saat_ini_note' => null,
            ]);

            ActivityLog::record(
                'Pengembalian Alat',
                "Alat '{$alkes->nama_barang}' telah dikembalikan dari peminjaman.",
                session('user_role_label', 'Admin/Petugas')
            );
        });

        return redirect()->route('peminjaman.index')->with('success', 'Alat berhasil dikembalikan ke lokasi asalnya.');
    }
}
