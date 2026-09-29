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
            'status_kalibrasi' => 'nullable|string',
            'tanggal_kalibrasi_terakhir' => 'nullable|date',
            'tanggal_kalibrasi_berikutnya' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'sertifikat_pdf' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $validated['status_kalibrasi'] = $validated['status_kalibrasi'] ?: 'SUDAH DIKALIBRASI';

        $alkes = Alkes::findOrFail($id);

        if ($request->hasFile('sertifikat_pdf')) {
            $file = $request->file('sertifikat_pdf');
            $detectedExt = strtolower($file->guessExtension() ?: $file->extension() ?: 'pdf');
            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            $ext = in_array($detectedExt, $allowedExts) ? $detectedExt : 'pdf';
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = 'sertifikat_' . $alkes->id . '_' . time() . '_' . substr($cleanName, 0, 30) . '.' . $ext;

            $file->storeAs('uploads/sertifikat', $fileName, 'public');

            $validated['sertifikat_kalibrasi'] = $fileName;

            $history = is_array($alkes->sertifikat_kalibrasi_history) ? $alkes->sertifikat_kalibrasi_history : [];
            $history[] = [
                'file_name' => $fileName,
                'file_path' => asset('storage/uploads/sertifikat/' . $fileName),
                'tahun' => !empty($validated['tanggal_kalibrasi_terakhir']) ? substr($validated['tanggal_kalibrasi_terakhir'], 0, 4) : date('Y'),
                'tanggal' => $validated['tanggal_kalibrasi_terakhir'] ?? date('Y-m-d'),
                'keterangan' => $validated['keterangan'] ?? 'Sertifikat Kalibrasi Resmi',
                'created_at' => now()->toIso8601String(),
            ];
            $validated['sertifikat_kalibrasi_history'] = $history;
        }

        unset($validated['sertifikat_pdf']);

        $alkes->update($validated);

        ActivityLog::record(
            'Update Kalibrasi',
            "Pembaruan status kalibrasi alkes '{$alkes->nama_barang}' menjadi '{$validated['status_kalibrasi']}'.",
            $alkes->ruangan->nama_ruangan ?? null
        );

        return redirect()->route('kalibrasi.index')->with('success', 'Data kalibrasi dan arsip dokumen alkes berhasil diperbarui.');
    }

    public function serveCertificate($filename)
    {
        $safeName = basename($filename);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExtensions)) {
            return redirect()->back()->with('error', 'Format dokumen tidak diizinkan.');
        }

        $storagePath = storage_path('app/public/uploads/sertifikat/' . $safeName);
        if (file_exists($storagePath)) {
            return response()->file($storagePath);
        }

        $dbPath = database_path('sertifikat/' . $safeName);
        if (file_exists($dbPath)) {
            return response()->file($dbPath);
        }

        return redirect()->back()->with('error', 'Dokumen sertifikat tidak ditemukan di server.');
    }
}
