<?php

namespace App\Http\Controllers;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\LogPemeliharaan;
use App\Models\MutasiAlkes;
use App\Models\Notification;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogPemeliharaanController extends Controller
{
    public function index(Request $request)
    {
        $query = LogPemeliharaan::with(['alkes.ruangan', 'alkes.lokasiRuangan']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi_kerusakan', 'like', "%{$search}%")
                  ->orWhere('tindakan_perbaikan', 'like', "%{$search}%")
                  ->orWhere('pelaksana_vendor', 'like', "%{$search}%")
                  ->orWhereHas('alkes', function ($aq) use ($search) {
                      $aq->where('nama_barang', 'like', "%{$search}%")
                         ->orWhere('nomor_seri', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('jenis_tindakan')) {
            $query->where('jenis_tindakan', $request->jenis_tindakan);
        }

        if ($request->filled('status_hasil')) {
            $query->where('status_hasil', $request->status_hasil);
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $logList = $query->latest()->paginate($perPage)->withQueryString();

        $notifications = Notification::with(['alkes', 'ruanganAsal'])
            ->latest()
            ->take(10)
            ->get();

        $unreadCount = Notification::where('dibaca', false)->count();

        $totalProses = LogPemeliharaan::where('status_hasil', 'Proses')->count();
        $totalSelesai = LogPemeliharaan::where('status_hasil', 'Selesai')->count();
        $totalLaporan = LogPemeliharaan::count();

        return view('pemeliharaan.index', compact('logList', 'notifications', 'unreadCount', 'totalProses', 'totalSelesai', 'totalLaporan'));
    }

    public function create(Request $request)
    {
        $selectedAlkesId = $request->query('alkes_id');
        $query = Alkes::with(['ruangan', 'lokasiRuangan'])->accessibleByCurrentRole();

        $alkesList = $query->orderBy('nama_barang', 'asc')->get();

        return view('pemeliharaan.create', compact('alkesList', 'selectedAlkesId'));
    }

    public function store(\App\Http\Requests\StorePemeliharaanRequest $request, \App\Services\FileUploadService $uploadService)
    {
        $validated = $request->validated();

        $fotoPath = null;
        if ($request->hasFile('foto_kerusakan')) {
            $fotoPath = $uploadService->uploadImage($request->file('foto_kerusakan'), 'kerusakan', 'rusak');
        }

        $tglMulai = $validated['tanggal_lapor'] ?? $validated['tanggal_mulai'] ?? now()->toDateTimeString();
        $gejala = $validated['gejala_kerusakan'] ?? $validated['deskripsi_kerusakan'] ?? 'Gejala kerusakan dilaporkan oleh petugas ruangan.';

        DB::transaction(function () use ($validated, $tglMulai, $gejala, $fotoPath) {
            $alkes = Alkes::with(['ruangan', 'lokasiRuangan'])->findOrFail($validated['alkes_id']);
            $elektromedisRuang = Ruangan::where('nama_ruangan', 'Elektromedis')->first();

            $ruanganAsalFisikId = $alkes->lokasi_ruangan_id ?: $alkes->ruangan_id;
            $ruanganTujuanId = $elektromedisRuang ? $elektromedisRuang->id : $ruanganAsalFisikId;

            $log = LogPemeliharaan::create([
                'alkes_id' => $alkes->id,
                'jenis_tindakan' => $validated['jenis_tindakan'],
                'tanggal_mulai' => $tglMulai,
                'pelaksana_vendor' => $validated['pelaksana_vendor'] ?? 'Teknisi Elektromedis RS',
                'deskripsi_kerusakan' => 'Gejala Ruangan: ' . $gejala,
                'foto_kerusakan' => $fotoPath,
                'tindakan_perbaikan' => $validated['tindakan_perbaikan'] ?? 'Dalam Proses Penanganan Elektromedis',
                'biaya' => $validated['biaya'] ?? 0,
                'status_hasil' => 'Proses',
            ]);

            MutasiAlkes::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $ruanganAsalFisikId,
                'ruangan_tujuan_id' => $ruanganTujuanId,
                'tanggal_mutasi' => now(),
                'pemohon' => session('user_role_label', 'Petugas Ruangan'),
                'penanggung_jawab' => 'Petugas Ruangan & ATEM Elektromedis',
                'alasan_mutasi' => 'Pengajuan ' . $validated['jenis_tindakan'] . ' - Unit Dipindahkan ke Ruangan Elektromedis',
                'status_persetujuan' => 'Disetujui',
            ]);

            $alkes->update([
                'status' => StatusAlkes::DALAM_PERBAIKAN->value,
                'kondisi' => KondisiAlkes::RUSAK_BERAT->value,
                'lokasi_ruangan_id' => $ruanganTujuanId,
                'lokasi_saat_ini_note' => 'Di Ruangan Elektromedis (Dalam Perbaikan)',
            ]);

            Notification::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $alkes->ruangan_id,
                'judul' => 'Laporan Kerusakan Masuk dari Ruang ' . ($alkes->ruangan->nama_ruangan ?? 'RS'),
                'pesan' => "Unit {$alkes->nama_barang} (SN: " . ($alkes->nomor_seri ?? '-') . ") dikirim dari Ruang " . ($alkes->ruangan->nama_ruangan ?? 'RS') . " ke Elektromedis untuk " . $validated['jenis_tindakan'] . '.',
                'tipe' => 'laporan_kerusakan',
            ]);

            ActivityLog::record(
                'Lapor Perbaikan',
                "Melaporkan kerusakan '{$alkes->nama_barang}'. Lokasi fisik unit otomatis dipindahkan ke Ruangan Elektromedis.",
                $alkes->ruangan->nama_ruangan ?? 'RS'
            );
        });

        return redirect()->route('pemeliharaan.index')->with('success', 'Laporan kerusakan berhasil dikirim! Mutasi fisik unit ke Ruangan Elektromedis telah otomatis tercatat.');
    }

    public function resolve(\App\Http\Requests\ResolvePemeliharaanRequest $request, $id)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($id, $validated) {
            $log = LogPemeliharaan::findOrFail($id);
            $alkes = Alkes::with(['ruangan', 'lokasiRuangan'])->findOrFail($log->alkes_id);
            $elektromedisRuang = Ruangan::where('nama_ruangan', 'Elektromedis')->first();

            $ruanganAsalElektroId = $elektromedisRuang ? $elektromedisRuang->id : $alkes->lokasi_ruangan_id;

            $now = now();

            $deskripsiBaru = $log->deskripsi_kerusakan;
            if ($validated['diagnosa_kerusakan']) {
                $deskripsiBaru .= "\nDiagnosa Elektromedis: " . $validated['diagnosa_kerusakan'];
            }

            $log->update([
                'status_hasil' => 'Selesai',
                'tanggal_selesai' => $now,
                'deskripsi_kerusakan' => $deskripsiBaru,
                'tindakan_perbaikan' => $validated['tindakan_perbaikan'],
                'pelaksana_vendor' => $validated['pelaksana_vendor'] ?: ($log->pelaksana_vendor ?: 'Teknisi Elektromedis RS'),
                'biaya' => $validated['biaya'] ?? $log->biaya,
            ]);

            MutasiAlkes::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $ruanganAsalElektroId,
                'ruangan_tujuan_id' => $alkes->ruangan_id,
                'tanggal_mutasi' => $now,
                'pemohon' => 'Ruangan Elektromedis (Admin)',
                'penanggung_jawab' => 'Teknisi Elektromedis RS',
                'alasan_mutasi' => 'Perbaikan & Kalibrasi Selesai - Unit Dikembalikan ke Ruangan Asal',
                'status_persetujuan' => 'Disetujui',
            ]);

            $alkes->update([
                'status' => StatusAlkes::TERSEDIA->value,
                'kondisi' => KondisiAlkes::BAIK->value,
                'lokasi_ruangan_id' => $alkes->ruangan_id,
                'lokasi_saat_ini_note' => null,
                'status_kalibrasi' => 'SUDAH DIKALIBRASI',
                'tanggal_kalibrasi_terakhir' => $now->toDateString(),
                'tanggal_kalibrasi_berikutnya' => $now->copy()->addYear()->toDateString(),
            ]);

            Notification::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $alkes->ruangan_id,
                'judul' => 'Perbaikan & Kalibrasi Selesai - Unit Dikembalikan ke ' . ($alkes->ruangan->nama_ruangan ?? 'Ruangan'),
                'pesan' => "Unit {$alkes->nama_barang} telah selesai diperbaiki & dikalibrasi ulang pada {$now->format('d M Y H:i')} WIB. Status kalibrasi otomatis diperbarui (Valid hingga " . $now->copy()->addYear()->format('d M Y') . "). Diagnosa: {$validated['diagnosa_kerusakan']}. Tindakan: {$validated['tindakan_perbaikan']}.",
                'tipe' => 'perbaikan_selesai',
            ]);

            // Otomatis tandai notifikasi laporan kerusakan terkait sebagai sudah dibaca
            Notification::where('alkes_id', $alkes->id)
                ->where('tipe', 'laporan_kerusakan')
                ->where('dibaca', false)
                ->update(['dibaca' => true]);

            ActivityLog::record(
                'Perbaikan & Kalibrasi Selesai',
                "Elektromedis menyelesaikan perbaikan & kalibrasi ulang '{$alkes->nama_barang}' pada {$now->format('d M Y H:i')} WIB. Riwayat kalibrasi diperbarui.",
                'Elektromedis'
            );
        });

        return redirect()->route('pemeliharaan.index')->with('success', 'Perbaikan berhasil diselesaikan! Diagnosa teknis, tindakan perbaikan, dan tanggal selesai telah tercatat otomatis.');
    }

    public function markNotificationsRead()
    {
        Notification::where('dibaca', false)->update(['dibaca' => true]);
        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
