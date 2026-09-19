<?php

namespace App\Http\Controllers;

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
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('peminjam_nama', 'ilike', "%{$s}%")
                  ->orWhereHas('alkes', function ($aq) use ($s) {
                      $aq->where('nama_barang', 'ilike', "%{$s}%")
                         ->orWhere('nomor_seri', 'ilike', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $isAll = $request->per_page === 'all';
        $perPage = $isAll ? max(1, (clone $query)->count()) : min(max((int) $request->get('per_page', 50), 1), 500);
        $peminjamanList = $query->latest()->paginate($perPage)->withQueryString();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $availableAlkes = Alkes::with('ruangan')
            ->where('status', 'Tersedia')
            ->orderBy('nama_barang', 'asc')
            ->get();

        return view('peminjaman.index', compact('peminjamanList', 'ruanganList', 'availableAlkes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alkes_id' => 'required|exists:alkes,id',
            'ruangan_peminjam_id' => 'required|exists:ruangan,id',
            'peminjam_nama' => 'required|string|max:255',
            'tanggal_pinjam' => 'required|date',
            'estimasi_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'keterangan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $alkes = Alkes::where('id', $validated['alkes_id'])->lockForUpdate()->firstOrFail();

            if ($alkes->status !== 'Tersedia') {
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
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            $alkes->update([
                'status' => 'Dipinjam',
                'lokasi_saat_ini_note' => 'Dipinjam oleh ' . $validated['peminjam_nama'] . ' - ' . $ruanganPeminjam->nama_ruangan,
            ]);

            ActivityLog::record(
                'Peminjaman Alat',
                "Alat '{$alkes->nama_barang}' (SN: " . ($alkes->nomor_seri ?: '-') . ") dipinjam oleh {$validated['peminjam_nama']} ({$ruanganPeminjam->nama_ruangan}).",
                $ruanganPeminjam->nama_ruangan
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

            $peminjaman->update([
                'status' => 'Dikembalikan',
                'tanggal_dikembalikan' => now(),
            ]);

            $alkes = Alkes::where('id', $peminjaman->alkes_id)->lockForUpdate()->first();
            if ($alkes) {
                $alkes->update([
                    'status' => 'Tersedia',
                    'lokasi_saat_ini_note' => null,
                ]);

                ActivityLog::record(
                    'Pengembalian Alat',
                    "Alat '{$alkes->nama_barang}' (SN: " . ($alkes->nomor_seri ?: '-') . ") telah dikembalikan ke ruangan asal.",
                    $alkes->ruangan->nama_ruangan ?? null
                );
            }
        });

        return redirect()->route('peminjaman.index')->with('success', 'Alat kesehatan berhasil ditandai telah dikembalikan ke ruangan asal.');
    }
}
