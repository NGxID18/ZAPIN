<?php

namespace App\Http\Controllers;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\Ruangan;
use App\Services\GoogleSheetSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlkesController extends Controller
{
    public function index(Request $request)
    {
        $query = Alkes::with(['ruangan', 'lokasiRuangan'])->accessibleByCurrentRole();

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_barang', 'ilike', "%{$s}%")
                  ->orWhere('merk', 'ilike', "%{$s}%")
                  ->orWhere('tipe', 'ilike', "%{$s}%")
                  ->orWhere('nomor_seri', 'ilike', "%{$s}%")
                  ->orWhere('cara_perolehan', 'ilike', "%{$s}%")
                  ->orWhere('nilai_perolehan', 'ilike', "%{$s}%")
                  ->orWhere('distributor', 'ilike', "%{$s}%")
                  ->orWhere('aspak', 'ilike', "%{$s}%")
                  ->orWhere('kib', 'ilike', "%{$s}%")
                  ->orWhere('non_kib_dan_aspak', 'ilike', "%{$s}%")
                  ->orWhere('akl_akd', 'ilike', "%{$s}%")
                  ->orWhere('keterangan', 'ilike', "%{$s}%")
                  ->orWhereHas('ruangan', function ($rq) use ($s) {
                      $rq->where('nama_ruangan', 'ilike', "%{$s}%");
                  })
                  ->orWhereHas('lokasiRuangan', function ($lq) use ($s) {
                      $lq->where('nama_ruangan', 'ilike', "%{$s}%");
                  });
            });
        }

        if ($request->filled('ruangan_id')) {
            $query->where('ruangan_id', $request->ruangan_id);
        }

        if ($request->filled('lokasi_ruangan_id')) {
            $query->where('lokasi_ruangan_id', $request->lokasi_ruangan_id);
        }

        if ($request->filled('kondisi')) {
            $val = trim($request->kondisi);
            if ($val === '-') {
                $query->whereNull('kondisi');
            } else {
                $query->whereRaw('UPPER(kondisi) = ?', [strtoupper($val)]);
            }
        }

        $sortBy = $request->input('sort_by', 'no_urut');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'no_urut' => 'no_urut',
            'nama_barang' => 'nama_barang',
            'merk' => 'merk',
            'tipe' => 'tipe',
            'nomor_seri' => 'nomor_seri',
            'tahun' => 'tahun',
            'cara_perolehan' => 'cara_perolehan',
            'nilai_perolehan' => 'nilai_perolehan',
            'distributor' => 'distributor',
            'kondisi' => 'kondisi',
            'aspak' => 'aspak',
            'kib' => 'kib',
            'non_kib_dan_aspak' => 'non_kib_dan_aspak',
            'akl_akd' => 'akl_akd',
            'keterangan' => 'keterangan',
            'created_at' => 'created_at',
        ];

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortDir);
        } else {
            $query->orderBy('no_urut', 'asc');
        }

        $isAll = $request->per_page === 'all';
        $perPage = $isAll ? max(1, (clone $query)->count()) : min(max((int) $request->get('per_page', 50), 1), 500);
        $alkesList = $query->paginate($perPage)->withQueryString();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();

        $kondisis = $this->getKondisiOptions(true);
        $statuses = $this->getStatusOptions();

        return view('alkes.index', compact('alkesList', 'ruanganList', 'kondisis', 'statuses', 'sortBy', 'sortDir'));
    }

    public function show($id)
    {
        $alkes = Alkes::with(['ruangan', 'lokasiRuangan', 'mutasi.ruanganAsal', 'mutasi.ruanganTujuan', 'logPemeliharaan', 'peminjaman.ruanganPeminjam'])->findOrFail($id);

        $mutasiTerbaru = $alkes->mutasi()->take(5)->get();
        $logPemeliharaanTerbaru = $alkes->logPemeliharaan()->take(5)->get();
        $peminjamanTerbaru = $alkes->peminjaman()->take(5)->get();

        return view('alkes.show', compact('alkes', 'mutasiTerbaru', 'logPemeliharaanTerbaru', 'peminjamanTerbaru'));
    }

    public function create()
    {
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $kondisis = $this->getKondisiOptions(false);
        $statuses = $this->getStatusOptions();

        return view('alkes.create', compact('ruanganList', 'kondisis', 'statuses'));
    }

    public function store(Request $request, GoogleSheetSyncService $syncService)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk' => 'nullable|string|max:255',
            'tipe' => 'nullable|string|max:255',
            'nomor_seri' => 'nullable|string|max:255',
            'tahun' => 'nullable|string|max:10',
            'tahun_pengadaan' => 'nullable|string|max:10',
            'jumlah' => 'nullable|integer|min:1',
            'cara_perolehan' => 'nullable|string|max:255',
            'nilai_perolehan' => 'nullable|string|max:255',
            'distributor' => 'nullable|string|max:255',
            'ruangan_id' => 'required|exists:ruangan,id',
            'status' => 'nullable|string|max:50',
            'kondisi' => 'nullable|string|max:50',
            'aspak' => 'nullable|string|max:50',
            'aspak_status' => 'nullable|string|max:50',
            'kib' => 'nullable|string|max:50',
            'kib_status' => 'nullable|string|max:50',
            'non_kib_dan_aspak' => 'nullable|string|max:255',
            'akl_akd' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        if (!empty($validated['tahun_pengadaan'])) {
            $validated['tahun'] = $validated['tahun_pengadaan'];
        }
        if ($request->filled('aspak')) {
            $validated['aspak'] = $request->input('aspak');
        } elseif ($request->filled('aspak_status')) {
            $validated['aspak'] = ($request->input('aspak_status') === 'TERDATA') ? 'TERDATA' : 'TIDAK TERDATA';
        }
        if ($request->filled('kib')) {
            $validated['kib'] = $request->input('kib');
        } elseif ($request->filled('kib_status')) {
            $validated['kib'] = ($request->input('kib_status') === 'TERDATA') ? 'TERDATA' : 'TIDAK TERDATA';
        }

        if (session('user_role') === 'ruangan' && session('user_ruangan_id')) {
            $validated['ruangan_id'] = (int) session('user_ruangan_id');
        }

        $validated['lokasi_ruangan_id'] = $validated['ruangan_id'];
        $validated['status'] = $validated['status'] ?? 'Tersedia';

        $totalUnits = max(1, (int) ($validated['jumlah'] ?? 1));
        $validated['jumlah'] = 1;

        $createdUnits = [];
        DB::transaction(function () use ($validated, $totalUnits, &$createdUnits) {
            $maxNo = Alkes::withTrashed()->max('no_urut') ?? 0;
            for ($i = 1; $i <= $totalUnits; $i++) {
                $itemData = $validated;
                $itemNo = $maxNo + $i;
                $itemData['no_urut'] = $itemNo;
                $itemData['kode_inventaris'] = sprintf('ALT-%s-%04d', $itemData['tahun'] ?? date('Y'), $itemNo);

                $alkes = Alkes::create($itemData);
                $createdUnits[] = $alkes;

                ActivityLog::record(
                    'Tambah Alkes',
                    "Registrasi unit alkes baru '{$alkes->nama_barang}' (No: {$alkes->no_urut}, SN: " . ($alkes->nomor_seri ?: '-') . ").",
                    $alkes->ruangan->nama_ruangan ?? null
                );
            }
        });

        if (count($createdUnits) > 1) {
            $syncService->pushBatchUpdateToSheet($createdUnits);
        } elseif (count($createdUnits) === 1) {
            $syncService->pushUpdateToSheet($createdUnits[0]->fresh());
        }

        $lastUnit = end($createdUnits);
        $redirectRuanganId = $lastUnit ? $lastUnit->ruangan_id : null;

        if ($totalUnits > 1) {
            $startNo = $createdUnits[0]->no_urut;
            $endNo = $lastUnit->no_urut;
            $msg = "{$totalUnits} unit alkes '{$lastUnit->nama_barang}' (No. {$startNo} s/d {$endNo}) berhasil ditambahkan ke sistem dan Google Spreadsheet.";
        } else {
            $msg = "Aset alkes '{$lastUnit->nama_barang}' (No. {$lastUnit->no_urut}) berhasil ditambahkan ke sistem dan Google Spreadsheet.";
        }

        return redirect()->route('alkes.index', ['ruangan_id' => $redirectRuanganId])
            ->with('success', $msg);
    }

    public function edit($id)
    {
        $alkes = Alkes::findOrFail($id);
        if (!$alkes->canBeManagedByCurrentRole()) {
            return redirect()->route('alkes.show', $alkes->id)
                ->with('error', 'Akses Ditolak: Anda hanya memiliki hak kendali atas alat kesehatan milik ruangan Anda.');
        }

        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $kondisis = $this->getKondisiOptions(false);
        $statuses = $this->getStatusOptions();

        return view('alkes.edit', compact('alkes', 'ruanganList', 'kondisis', 'statuses'));
    }

    public function update(Request $request, $id, GoogleSheetSyncService $syncService)
    {
        $alkes = Alkes::findOrFail($id);
        if (!$alkes->canBeManagedByCurrentRole()) {
            return redirect()->route('alkes.show', $alkes->id)
                ->with('error', 'Akses Ditolak: Anda hanya memiliki hak kendali atas alat kesehatan milik ruangan Anda.');
        }

        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk' => 'nullable|string|max:255',
            'tipe' => 'nullable|string|max:255',
            'nomor_seri' => 'nullable|string|max:255',
            'tahun' => 'nullable|string|max:10',
            'tahun_pengadaan' => 'nullable|string|max:10',
            'jumlah' => 'nullable|integer|min:1',
            'cara_perolehan' => 'nullable|string|max:255',
            'nilai_perolehan' => 'nullable|string|max:255',
            'distributor' => 'nullable|string|max:255',
            'ruangan_id' => 'required|exists:ruangan,id',
            'kondisi' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'aspak' => 'nullable|string|max:50',
            'aspak_status' => 'nullable|string|max:50',
            'kib' => 'nullable|string|max:50',
            'kib_status' => 'nullable|string|max:50',
            'non_kib_dan_aspak' => 'nullable|string|max:255',
            'akl_akd' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        if (session('user_role') === 'ruangan') {
            $validated['ruangan_id'] = $alkes->ruangan_id;
        }

        if (isset($validated['tahun_pengadaan']) && !empty($validated['tahun_pengadaan'])) {
            $validated['tahun'] = $validated['tahun_pengadaan'];
        }
        if ($request->has('aspak')) {
            $validated['aspak'] = $request->input('aspak');
        } elseif ($request->has('aspak_status')) {
            $validated['aspak'] = ($request->input('aspak_status') === 'TERDATA') ? 'TERDATA' : 'TIDAK TERDATA';
        }
        if ($request->has('kib')) {
            $validated['kib'] = $request->input('kib');
        } elseif ($request->has('kib_status')) {
            $validated['kib'] = ($request->input('kib_status') === 'TERDATA') ? 'TERDATA' : 'TIDAK TERDATA';
        }

        $alkes->update($validated);

        ActivityLog::record('Update Alkes', "Pembaruan informasi data alkes '{$alkes->nama_barang}'.", $alkes->ruangan->nama_ruangan ?? null);

        $syncResult = $syncService->pushUpdateToSheet($alkes->fresh());
        $message = "Data alkes '{$alkes->nama_barang}' berhasil diperbarui.";
        if (!empty($syncResult['success'])) {
            $message .= " Data otomatis tersinkronisasi ke Google Spreadsheet.";
        }

        return redirect()->route('alkes.show', $alkes->id)->with('success', $message);
    }

    public function handleSheetWebhookUpdate(Request $request, GoogleSheetSyncService $syncService)
    {
        $incomingSecret = (string) ($request->header('X-Zapin-Secret') ?? $request->input('secret') ?? '');
        $expectedSecret = (string) config('zapin.api_key');

        if (empty($expectedSecret) || !hash_equals($expectedSecret, $incomingSecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Kunci API rahasia (secret key) tidak valid atau tidak disertakan.',
            ], 401);
        }

        $action = $request->input('action');
        if ($action === 'reconcile_active_rows') {
            $activeNoUruts = $request->input('active_no_uruts', []);
            $result = $syncService->reconcileActiveRows($activeNoUruts);
            return response()->json($result);
        }

        $payload = $request->all();
        $result = $syncService->updateFromSheetWebhook($payload);

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Gagal memproses sinkronisasi dari Google Sheets.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'action' => $result['action'] ?? 'updated',
            'message' => "Data alkes '{$result['nama_barang']}' berhasil disinkronkan ke sistem ZAPIN.",
            'alkes_id' => $result['alkes_id'] ?? null,
        ]);
    }

    public function destroy($id, GoogleSheetSyncService $syncService)
    {
        $alkes = Alkes::findOrFail($id);
        if (!$alkes->canBeManagedByCurrentRole()) {
            return redirect()->route('alkes.show', $alkes->id)
                ->with('error', 'Akses Ditolak: Anda hanya berhak menghapus alat kesehatan milik ruangan Anda.');
        }

        $nama = $alkes->nama_barang;
        $noUrut = $alkes->no_urut;
        $alkes->delete();

        ActivityLog::record('Hapus Alkes', "Penghapusan data alkes '{$nama}' (No: {$noUrut}).");

        if ($noUrut) {
            $syncService->pushDeleteToSheet($noUrut);
        }

        return redirect()->route('alkes.index')
            ->with('success', "Aset alkes '{$nama}' (No: {$noUrut}) berhasil dihapus dari sistem dan Google Spreadsheet.");
    }

    public function syncGoogleSheets(GoogleSheetSyncService $syncService)
    {
        try {
            $result = $syncService->sync();
            return redirect()->back()->with('success', "Sinkronisasi Google Spreadsheet berhasil! {$result['created']} data baru ditambahkan, {$result['updated']} data diperbarui (Total {$result['total']} alkes).");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Gagal sinkronisasi Google Spreadsheet: " . $e->getMessage());
        }
    }

    protected function getKondisiOptions(bool $includeEmpty = false): array
    {
        $options = [
            KondisiAlkes::BAIK,
            KondisiAlkes::RUSAK_RINGAN,
            KondisiAlkes::RUSAK_BERAT,
        ];

        if ($includeEmpty) {
            $options[] = KondisiAlkes::UNKNOWN;
        }

        return $options;
    }

    protected function getStatusOptions(): array
    {
        return [
            StatusAlkes::TERSEDIA,
            StatusAlkes::DIPINJAM,
            StatusAlkes::DALAM_PERBAIKAN,
        ];
    }
}
