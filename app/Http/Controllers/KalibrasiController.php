<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\Ruangan;
use Illuminate\Http\Request;

class KalibrasiController extends Controller
{
    public function index(Request $request)
    {
        $query = Alkes::with(['ruangan', 'lokasiRuangan'])->accessibleByCurrentRole();

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_barang', 'ilike', "%{$s}%")
                  ->orWhere('merk', 'ilike', "%{$s}%")
                  ->orWhere('nomor_seri', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('ruangan_id')) {
            $query->where('ruangan_id', $request->ruangan_id);
        }

        if ($request->filled('status_kalibrasi')) {
            $status = $request->status_kalibrasi;
            if ($status === 'TERKALIBRASI') {
                $query->where('status_kalibrasi', 'SUDAH DIKALIBRASI');
            } elseif ($status === 'EXPIRED') {
                $query->whereNotNull('tanggal_kalibrasi_berikutnya')
                      ->where('tanggal_kalibrasi_berikutnya', '<', now()->toDateString());
            } elseif ($status === 'BELUM') {
                $query->where('status_kalibrasi', '!=', 'SUDAH DIKALIBRASI');
            }
        }

        $isAll = $request->per_page === 'all';
        $perPage = $isAll ? max(1, (clone $query)->count()) : min(max((int) $request->get('per_page', 50), 1), 500);
        $alkesList = $query->orderBy('no_urut', 'asc')->paginate($perPage)->withQueryString();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();

        $totalAlkes = Alkes::count();
        $totalTerkalibrasi = Alkes::where('status_kalibrasi', 'SUDAH DIKALIBRASI')->count();
        $totalExpired = Alkes::whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '<', now()->toDateString())
            ->count();
        $totalBelum = Alkes::where('status_kalibrasi', '!=', 'SUDAH DIKALIBRASI')->count();

        return view('kalibrasi.index', compact(
            'alkesList',
            'ruanganList',
            'totalAlkes',
            'totalTerkalibrasi',
            'totalExpired',
            'totalBelum'
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status_kalibrasi' => 'required|string',
            'tanggal_kalibrasi_terakhir' => 'nullable|date',
            'tanggal_kalibrasi_berikutnya' => 'nullable|date',
        ]);

        $alkes = Alkes::findOrFail($id);
        $alkes->update($validated);

        ActivityLog::record(
            'Update Kalibrasi',
            "Pembaruan status kalibrasi alkes '{$alkes->nama_barang}' menjadi '{$validated['status_kalibrasi']}'.",
            $alkes->ruangan->nama_ruangan ?? null
        );

        return redirect()->route('kalibrasi.index')->with('success', 'Data kalibrasi alkes berhasil diperbarui.');
    }

    public function serveCertificate($filename)
    {
        $filePath = database_path('sertifikat/' . $filename);
        if (file_exists($filePath)) {
            return response()->file($filePath);
        }
        return redirect()->back()->with('error', 'Dokumen sertifikat tidak ditemukan di server.');
    }
}
