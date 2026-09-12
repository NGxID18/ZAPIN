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
            $escaped = addcslashes(trim($request->search), '%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('peminjam_nama', 'like', "%{$escaped}%")
                  ->orWhereHas('alkes', function ($aq) use ($escaped) {
                      $aq->where('nama_barang', 'like', "%{$escaped}%")
                         ->orWhere('nomor_seri', 'like', "%{$escaped}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $peminjamanList = $query->latest()->paginate($perPage)->withQueryString();
        $ruanganList = \Illuminate\Support\Facades\Cache::remember('ruangan_list', 86400, fn() => Ruangan::orderBy('nama_ruangan', 'asc')->get());
        $availableAlkes = Alkes::with('ruangan')
            ->where('status', StatusAlkes::TERSEDIA->value)
            ->orderBy('nama_barang', 'asc')
            ->get();

        return view('peminjaman.index', compact('peminjamanList', 'ruanganList', 'availableAlkes'));
    }

    public function store(\App\Http\Requests\StorePeminjamanRequest $request)
    {
        $validated = $request->validated();

        // Otorisasi: Petugas ruangan hanya boleh meminjam atas nama ruangannya sendiri
        if (session('user_role') === 'ruangan' && session('user_ruangan_id')) {
            $userRuanganId = (int) session('user_ruangan_id');
            if ((int) $validated['ruangan_peminjam_id'] !== $userRuanganId) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang mengajukan peminjaman atas nama ruangan Anda sendiri.');
            }
        }

        DB::transaction(function () use ($validated) {
            $alkes = Alkes::where('id', $validated['alkes_id'])->lockForUpdate()->firstOrFail();
            
            $statusVal = $alkes->status instanceof StatusAlkes ? $alkes->status->value : (string) $alkes->status;
            if ($statusVal !== StatusAlkes::TERSEDIA->value) {
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
            $peminjaman = PeminjamanAlkes::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($peminjaman->status === 'Dikembalikan') {
                abort(422, 'Peminjaman alat ini sudah dikembalikan sebelumnya.');
            }

            $alkes = Alkes::where('id', $peminjaman->alkes_id)->lockForUpdate()->firstOrFail();

            // Otorisasi BOLA: Hanya ruangan peminjam, ruangan pemilik alat, atau elektromedis yang berhak menyelesaikan pengembalian
            if (session('user_role') === 'ruangan' && session('user_ruangan_id')) {
                $userRuanganId = (int) session('user_ruangan_id');
                if ((int) $peminjaman->ruangan_peminjam_id !== $userRuanganId && (int) $alkes->ruangan_id !== $userRuanganId) {
                    abort(403, 'Akses Ditolak: Hanya ruangan peminjam, ruangan pemilik alat, atau Instalasi Elektromedis yang berwenang menandai pengembalian.');
                }
            }

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
