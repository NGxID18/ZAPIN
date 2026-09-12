<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KalibrasiController extends Controller
{
    public function index(Request $request)
    {
        $baseScope = Alkes::accessibleByCurrentRole();
        $query = (clone $baseScope)->with(['ruangan', 'lokasiRuangan', 'nomenklatur']);

        if ($request->filled('search')) {
            $escaped = addcslashes(trim($request->search), '%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('nama_barang', 'like', "%{$escaped}%")
                  ->orWhere('merk', 'like', "%{$escaped}%")
                  ->orWhere('tipe', 'like', "%{$escaped}%")
                  ->orWhere('nomor_seri', 'like', "%{$escaped}%")
                  ->orWhere('kode_inventaris', 'like', "%{$escaped}%");
            });
        }

        if ($request->filled('ruangan_id')) {
            $query->where('ruangan_id', $request->ruangan_id);
        }

        if ($request->filled('status_kalibrasi')) {
            $status = $request->status_kalibrasi;
            $today = now()->toDateString();

            if ($status === 'TERKALIBRASI') {
                $query->where('status_kalibrasi', 'SUDAH DIKALIBRASI');
            } elseif ($status === 'EXPIRED') {
                $query->whereNotNull('tanggal_kalibrasi_berikutnya')
                      ->where('tanggal_kalibrasi_berikutnya', '<', $today);
            } elseif ($status === 'BELUM') {
                $query->where('status_kalibrasi', '!=', 'SUDAH DIKALIBRASI');
            }
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $alkesList = $query->orderByRaw('CASE WHEN status_kalibrasi = "SUDAH DIKALIBRASI" THEN 0 ELSE 1 END')
                          ->orderBy('nama_barang', 'asc')
                          ->paginate($perPage)
                          ->withQueryString();

        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();

        $totalAlkes = (clone $baseScope)->count();
        $totalTerkalibrasi = (clone $baseScope)->where('status_kalibrasi', 'SUDAH DIKALIBRASI')->count();
        $totalExpired = (clone $baseScope)->whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '<', now()->toDateString())
            ->count();
        $totalBelum = (clone $baseScope)->where('status_kalibrasi', '!=', 'SUDAH DIKALIBRASI')->count();

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

        $request->validate([
            'tanggal_kalibrasi_terakhir' => 'required|date',
            'tanggal_kalibrasi_berikutnya' => 'required|date|after_or_equal:tanggal_kalibrasi_terakhir',
            'sertifikat_pdf' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // Max 10MB (PDF/Gambar)
            'keterangan' => 'nullable|string|max:500',
        ]);

        $alkes = Alkes::with('ruangan')->findOrFail($id);

        $tglTerakhir = $request->tanggal_kalibrasi_terakhir;
        $tglBerikutnya = $request->tanggal_kalibrasi_berikutnya;

        $isFutureOrToday = Carbon::parse($tglBerikutnya)->isToday() || Carbon::parse($tglBerikutnya)->isFuture();
        $updateData = [
            'tanggal_kalibrasi_terakhir' => $tglTerakhir,
            'tanggal_kalibrasi_berikutnya' => $tglBerikutnya,
            'status_kalibrasi' => $isFutureOrToday ? 'SUDAH DIKALIBRASI' : 'BELUM DIKALIBRASI',
        ];

        if ($request->hasFile('sertifikat_pdf')) {
            $file = $request->file('sertifikat_pdf');
            $uploadDir = storage_path('app/sertifikat');
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            $rawExt = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $extension = in_array($rawExt, $allowedExtensions, true) ? $rawExt : 'pdf';
            $filename = 'sertifikat_' . $alkes->id . '_' . time() . '.' . $extension;
            $file->move($uploadDir, $filename);
            $filePath = '/database/sertifikat/' . $filename;
            $updateData['sertifikat_kalibrasi'] = $filePath;

            $history = is_array($alkes->sertifikat_kalibrasi_history) ? $alkes->sertifikat_kalibrasi_history : [];

            if ($alkes->sertifikat_kalibrasi && empty($history)) {
                $history[] = [
                    'tahun' => $alkes->tanggal_kalibrasi_terakhir ? Carbon::parse($alkes->tanggal_kalibrasi_terakhir)->format('Y') : date('Y'),
                    'tanggal' => $alkes->tanggal_kalibrasi_terakhir ? Carbon::parse($alkes->tanggal_kalibrasi_terakhir)->format('d/m/Y') : '-',
                    'file_path' => $alkes->sertifikat_kalibrasi,
                    'keterangan' => 'Sertifikat Kalibrasi Terdaftar',
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }

            $tahunInput = Carbon::parse($tglTerakhir)->format('Y');
            $history[] = [
                'tahun' => $tahunInput,
                'tanggal' => Carbon::parse($tglTerakhir)->format('d/m/Y'),
                'file_path' => $filePath,
                'keterangan' => $request->keterangan ?: ("Sertifikat Kalibrasi Tahun " . $tahunInput),
                'uploaded_at' => now()->toDateTimeString(),
            ];

            $updateData['sertifikat_kalibrasi_history'] = array_values($history);
        }

        if ($request->filled('keterangan')) {
            $catatanLama = $alkes->keterangan ? $alkes->keterangan . ' | ' : '';
            $updateData['keterangan'] = $catatanLama . '[Kalibrasi ' . Carbon::parse($tglTerakhir)->format('d/m/Y') . ']: ' . $request->keterangan;
        }

        $alkes->update($updateData);

        ActivityLog::record(
            'Pembaruan Kalibrasi',
            "Memperbarui sertifikat PDF & jadwal kalibrasi alat '{$alkes->nama_barang}' (SN: " . ($alkes->nomor_seri ?: '-') . "). Tanggal Kalibrasi Terakhir: " . Carbon::parse($tglTerakhir)->format('d/m/Y') . ", Jadwal Ulang: " . Carbon::parse($tglBerikutnya)->format('d/m/Y') . '.',
            session('user_role_label', 'Instalasi Elektromedis')
        );

        return redirect()->back()->with('success', "Sertifikat & Jadwal Kalibrasi untuk unit '{$alkes->nama_barang}' berhasil diperbarui!");
    }

    public function serveCertificate($filename)
    {
        $safeFilename = basename($filename);

        // Validasi whitelist format ekstensi file sertifikat
        $ext = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExtensions, true)) {
            abort(403, 'Akses Ditolak: Format dokumen sertifikat tidak diizinkan.');
        }

        // 1. Cek penyimpanan standar baru di storage/app/sertifikat
        $storagePath = storage_path('app/sertifikat/' . $safeFilename);
        if (file_exists($storagePath)) {
            return response()->file($storagePath);
        }

        // 2. Fallback untuk data lama di database/sertifikat
        $legacyDatabasePath = base_path('database/sertifikat/' . $safeFilename);
        if (file_exists($legacyDatabasePath)) {
            return response()->file($legacyDatabasePath);
        }

        // 3. Fallback untuk data lama di public/uploads/sertifikat
        $oldPublicPath = public_path('uploads/sertifikat/' . $safeFilename);
        if (file_exists($oldPublicPath)) {
            return response()->file($oldPublicPath);
        }

        abort(404, 'Dokumen sertifikat tidak ditemukan.');
    }
}
