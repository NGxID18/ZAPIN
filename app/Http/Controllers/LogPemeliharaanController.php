<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\LogPemeliharaan;
use App\Services\GoogleSheetSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogPemeliharaanController extends Controller
{
    public function index(Request $request)
    {
        $query = LogPemeliharaan::with(['alkes.ruangan', 'alkes.lokasiRuangan']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('deskripsi_kerusakan', 'ilike', "%{$s}%")
                  ->orWhere('tindakan_perbaikan', 'ilike', "%{$s}%")
                  ->orWhere('pelaksana_vendor', 'ilike', "%{$s}%")
                  ->orWhereHas('alkes', function ($aq) use ($s) {
                      $aq->where('nama_barang', 'ilike', "%{$s}%")
                         ->orWhere('nomor_seri', 'ilike', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status_hasil')) {
            $query->where('status_hasil', $request->status_hasil);
        }

        $isAll = $request->per_page === 'all';
        $perPage = $isAll ? max(1, (clone $query)->count()) : min(max((int) $request->get('per_page', 50), 1), 500);
        $logList = $query->latest()->paginate($perPage)->withQueryString();

        $notifications = collect([]);
        $unreadCount = 0;
        $totalProses = LogPemeliharaan::where('status_hasil', 'Proses')->count();
        $totalSelesai = LogPemeliharaan::where('status_hasil', 'Selesai')->count();
        $totalLaporan = LogPemeliharaan::count();

        return view('pemeliharaan.index', compact(
            'logList',
            'notifications',
            'unreadCount',
            'totalProses',
            'totalSelesai',
            'totalLaporan'
        ));
    }

    public function create(Request $request)
    {
        $selectedAlkesId = $request->query('alkes_id');
        $alkesQuery = Alkes::with(['ruangan', 'lokasiRuangan'])->orderBy('nama_barang', 'asc');

        if (session('user_role') === 'ruangan' && session('user_ruangan_id')) {
            $myRoom = (int) session('user_ruangan_id');
            $alkesQuery->where(function ($q) use ($myRoom) {
                $q->where('ruangan_id', $myRoom)
                  ->orWhere('lokasi_ruangan_id', $myRoom);
            });
        }

        $alkesList = $alkesQuery->get();

        return view('pemeliharaan.create', compact('alkesList', 'selectedAlkesId'));
    }

    public function store(Request $request)
    {
        if ($request->filled('gejala_kerusakan') && !$request->filled('deskripsi_kerusakan')) {
            $request->merge(['deskripsi_kerusakan' => $request->input('gejala_kerusakan')]);
        }

        $validated = $request->validate([
            'alkes_id' => 'required|exists:alkes,id',
            'deskripsi_kerusakan' => 'required|string',
            'jenis_tindakan' => 'nullable|string',
            'tanggal_lapor' => 'nullable|date',
            'foto_kerusakan' => 'nullable|file|image|max:10240',
        ]);

        $alkes = Alkes::findOrFail($validated['alkes_id']);
        if (!$alkes->canBeOperatedByCurrentRole()) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki hak untuk melaporkan kerusakan alat kesehatan di ruangan Anda.');
        }

        $fotoPath = null;
        if ($request->hasFile('foto_kerusakan')) {
            $file = $request->file('foto_kerusakan');
            $detectedExt = strtolower($file->guessExtension() ?: $file->extension() ?: 'jpg');
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = in_array($detectedExt, $allowedExts) ? $detectedExt : 'jpg';
            $filename = 'rusak_' . time() . '_' . uniqid() . '.' . $ext;
            $fotoPath = $file->storeAs('uploads/kerusakan', $filename, 'public');
        }

        DB::transaction(function () use ($alkes, $validated, $request, $fotoPath) {
            $log = LogPemeliharaan::create([
                'alkes_id' => $alkes->id,
                'jenis_tindakan' => $validated['jenis_tindakan'] ?? 'Perbaikan Fisik',
                'tanggal_mulai' => $request->filled('tanggal_lapor') ? $request->tanggal_lapor : now(),
                'deskripsi_kerusakan' => $validated['deskripsi_kerusakan'],
                'foto_kerusakan' => $fotoPath,
                'status_hasil' => 'Proses',
            ]);

            $alkes->update([
                'status' => 'Dalam Perbaikan',
                'kondisi' => 'RUSAK RINGAN',
                'lokasi_saat_ini_note' => 'Dalam Perbaikan Elektromedis',
            ]);

            ActivityLog::record(
                'Lapor Kerusakan',
                "Pelaporan kerusakan alkes '{$alkes->nama_barang}' (SN: " . ($alkes->nomor_seri ?: '-') . "): {$validated['deskripsi_kerusakan']}",
                $alkes->ruangan->nama_ruangan ?? null
            );
        });

        app(GoogleSheetSyncService::class)->pushUpdateToSheet($alkes->fresh());

        return redirect()->route('pemeliharaan.index')->with('success', 'Laporan kerusakan alkes berhasil dikirim ke Instalasi Elektromedis.');
    }

    public function resolve(Request $request, $id)
    {
        $validated = $request->validate([
            'tindakan_perbaikan' => 'required|string',
            'biaya' => 'nullable|numeric|min:0',
            'pelaksana_vendor' => 'nullable|string',
        ]);

        $alkes = null;
        DB::transaction(function () use ($id, $validated, &$alkes) {
            $log = LogPemeliharaan::where('id', $id)->lockForUpdate()->firstOrFail();
            $log->update([
                'tindakan_perbaikan' => $validated['tindakan_perbaikan'],
                'biaya' => $validated['biaya'] ?? 0,
                'pelaksana_vendor' => $validated['pelaksana_vendor'] ?? 'Teknisi Elektromedis RS',
                'tanggal_selesai' => now(),
                'status_hasil' => 'Selesai',
            ]);

            $alkes = Alkes::where('id', $log->alkes_id)->lockForUpdate()->first();
            if ($alkes) {
                $alkes->update([
                    'status' => 'Tersedia',
                    'kondisi' => 'BAIK',
                    'lokasi_saat_ini_note' => null,
                ]);

                ActivityLog::record(
                    'Perbaikan Selesai',
                    "Perbaikan alkes '{$alkes->nama_barang}' selesai ditangani: {$validated['tindakan_perbaikan']}",
                    $alkes->ruangan->nama_ruangan ?? null
                );
            }
        });

        if ($alkes) {
            app(GoogleSheetSyncService::class)->pushUpdateToSheet($alkes->fresh());
        }

        return redirect()->route('pemeliharaan.index')->with('success', 'Perbaikan alkes berhasil ditandai selesai dan unit kembali beroperasi normal.');
    }

    public function markNotificationsRead()
    {
        return response()->json(['status' => 'success']);
    }
}
