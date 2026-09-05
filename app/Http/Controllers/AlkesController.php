<?php

namespace App\Http\Controllers;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\LogPemeliharaan;
use App\Models\Nomenklatur;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreAlkesRequest;
use App\Http\Requests\UpdateAlkesRequest;
use App\Services\AlkesSyncService;

class AlkesController extends Controller
{
    public function index(Request $request)
    {
        $query = Alkes::with(['nomenklatur', 'ruangan', 'lokasiRuangan'])->accessibleByCurrentRole();

        if ($request->filled('search')) {
            $query->search(trim($request->search));
        }

        if ($request->filled('ruangan_id')) {
            $query->where('ruangan_id', $request->ruangan_id);
        }

        if ($request->filled('lokasi_ruangan_id')) {
            $query->where('lokasi_ruangan_id', $request->lokasi_ruangan_id);
        }

        if ($request->filled('kondisi')) {
            $val = trim($request->kondisi);
            $query->where(function ($q) use ($val) {
                $q->where('kondisi', $val)
                  ->orWhere('kondisi', strtolower($val))
                  ->orWhere('kondisi', strtoupper($val));
            });
        }

        $sortBy = $request->input('sort_by', 'nama_barang');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'nama_barang' => 'nama_barang',
            'merk' => 'merk',
            'tipe' => 'tipe',
            'nomor_seri' => 'nomor_seri',
            'tahun_pengadaan' => 'tahun_pengadaan',
            'jumlah' => 'jumlah',
            'kondisi' => 'kondisi',
            'aspak_status' => 'aspak_status',
            'kib_status' => 'kib_status',
            'created_at' => 'created_at',
        ];

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortDir);
        } elseif ($sortBy === 'ruangan') {
            $query->join('ruangan', 'alkes.ruangan_id', '=', 'ruangan.id')
                  ->orderBy('ruangan.nama_ruangan', $sortDir)
                  ->select('alkes.*');
        } else {
            $query->orderBy('nama_barang', 'asc');
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $alkesList = $query->paginate($perPage)->withQueryString();
        $ruanganList = \Illuminate\Support\Facades\Cache::remember('ruangan_list', 86400, fn() => Ruangan::orderBy('nama_ruangan', 'asc')->get());

        $statuses = StatusAlkes::cases();
        $kondisis = KondisiAlkes::cases();

        return view('alkes.index', compact('alkesList', 'ruanganList', 'statuses', 'kondisis', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $nomenklaturList = Nomenklatur::all();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $statuses = StatusAlkes::cases();
        $kondisis = KondisiAlkes::cases();

        return view('alkes.create', compact('nomenklaturList', 'ruanganList', 'statuses', 'kondisis'));
    }

    public function store(StoreAlkesRequest $request)
    {
        $validated = $request->validated();
        $validated['lokasi_ruangan_id'] = $validated['ruangan_id'];

        $alkes = DB::transaction(function () use ($validated) {
            if (empty($validated['kode_inventaris'])) {
                $validated['kode_inventaris'] = Alkes::generateKodeInventaris();
            }

            $alkes = Alkes::create($validated);
            $ruang = Ruangan::find($validated['ruangan_id']);

            ActivityLog::record('Tambah Alkes', "Registrasi aset alkes baru '{$alkes->nama_barang}'.", $ruang->nama_ruangan ?? 'Pusat');

            return $alkes;
        });

        return redirect()->route('alkes.index', ['ruangan_id' => $alkes->ruangan_id])
            ->with('success', 'Data Alat Kesehatan berhasil ditambahkan!');
    }

    public function show($id)
    {
        $alkes = Alkes::with(['nomenklatur', 'ruangan', 'lokasiRuangan', 'mutasi.ruanganAsal', 'mutasi.ruanganTujuan', 'logPemeliharaan'])->findOrFail($id);

        return view('alkes.show', compact('alkes'));
    }

    public function edit($id)
    {
        $alkes = Alkes::with(['nomenklatur', 'ruangan', 'lokasiRuangan'])->findOrFail($id);

        $nomenklaturList = Nomenklatur::all();
        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $statuses = StatusAlkes::cases();
        $kondisis = KondisiAlkes::cases();

        return view('alkes.edit', compact('alkes', 'nomenklaturList', 'ruanganList', 'statuses', 'kondisis'));
    }

    public function update(UpdateAlkesRequest $request, $id)
    {
        $alkes = Alkes::findOrFail($id);

        $validated = $request->validated();

        $alkes->update($validated);

        ActivityLog::record('Edit Alkes', "Memperbarui informasi aset alkes '{$alkes->nama_barang}'.", $alkes->ruangan->nama_ruangan ?? 'Pusat');

        return redirect()->route('alkes.show', $alkes->id)
            ->with('success', 'Data Alat Kesehatan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $alkes = Alkes::findOrFail($id);

        $namaAlat = $alkes->nama_barang;
        $alkes->delete();

        ActivityLog::record('Hapus Alkes', "Menghapus data aset alkes '{$namaAlat}'.");

        return redirect()->route('alkes.index')
            ->with('success', 'Data Alat Kesehatan berhasil dihapus!');
    }

    public function apiIndex()
    {
        $items = Alkes::with(['ruangan', 'lokasiRuangan'])
            ->leftJoin('ruangan', 'alkes.ruangan_id', '=', 'ruangan.id')
            ->orderBy('ruangan.nama_ruangan', 'asc')
            ->orderBy('alkes.nama_barang', 'asc')
            ->select('alkes.*')
            ->get();

        $data = $items->map(function ($item, $index) {
            $lokasiNama = $item->lokasiRuangan->nama_ruangan ?? ($item->ruangan->nama_ruangan ?? '-');
            return [
                'id' => $item->id,
                'no' => $index + 1,
                'nama_barang' => $item->nama_barang,
                'merk' => $item->merk ?: '-',
                'tipe' => $item->tipe ?: '-',
                'seri_number' => $item->nomor_seri ?: '-',
                'tahun' => $item->tahun_pengadaan ?: '-',
                'ruang_pemilik' => $item->ruangan->nama_ruangan ?? '-',
                'lokasi_alkes' => $lokasiNama,
                'lokasi_fisik' => $lokasiNama,
                'kondisi' => $item->kondisi_enum->label(),
                'status_kalibrasi' => $item->status_kalibrasi ?: 'BELUM DIKALIBRASI',
                'tanggal_kalibrasi_terakhir' => $item->tanggal_kalibrasi_terakhir ? $item->tanggal_kalibrasi_terakhir->format('Y-m-d') : 'Belum ada data',
                'keterangan' => $item->keterangan ?: '-'
            ];
        });

        return response()->json([
            'status' => 'success',
            'total' => count($data),
            'updated_at' => now()->toDateTimeString(),
            'data' => $data
        ]);
    }

    public function apiSheetsData()
    {
        // 1. Alkes Data
        $alkesItems = Alkes::with(['ruangan', 'lokasiRuangan'])
            ->leftJoin('ruangan', 'alkes.ruangan_id', '=', 'ruangan.id')
            ->orderBy('ruangan.nama_ruangan', 'asc')
            ->orderBy('alkes.nama_barang', 'asc')
            ->select('alkes.*')
            ->get();

        $alkesData = $alkesItems->map(function ($item, $index) {
            $lokasiNama = $item->lokasiRuangan->nama_ruangan ?? ($item->ruangan->nama_ruangan ?? '-');
            return [
                'id' => $item->id,
                'no' => $index + 1,
                'nama_barang' => $item->nama_barang,
                'merk' => $item->merk ?: '-',
                'tipe' => $item->tipe ?: '-',
                'seri_number' => $item->nomor_seri ?: '-',
                'tahun' => $item->tahun_pengadaan ?: '-',
                'ruang_pemilik' => $item->ruangan->nama_ruangan ?? '-',
                'lokasi_alkes' => $lokasiNama,
                'lokasi_fisik' => $lokasiNama,
                'kondisi' => $item->kondisi_enum->label(),
                'keterangan' => $item->keterangan ?: '-'
            ];
        });

        // 2. Pemeliharaan / Perbaikan Data
        $logItems = LogPemeliharaan::with(['alkes.ruangan', 'alkes.lokasiRuangan'])
            ->orderBy('id', 'desc')
            ->get();

        $pemeliharaanData = $logItems->map(function ($log, $index) {
            $alkes = $log->alkes;
            $ruangan = $alkes->ruangan->nama_ruangan ?? '-';
            $merk = $alkes->merk ?: '-';
            $tipe = $alkes->tipe ?: '-';
            $merkTipe = ($merk !== '-' || $tipe !== '-') ? trim("{$merk} / {$tipe}", ' /') : '-';
            
            $startDate = $log->tanggal_mulai ?: $log->created_at;
            $bulanPelaporan = $startDate ? $startDate->translatedFormat('F Y') : '-';

            return [
                'no' => $index + 1,
                'nama_barang' => $alkes->nama_barang ?? '-',
                'merk_tipe' => $merkTipe,
                'seri_number' => $alkes->nomor_seri ?: '-',
                'ruang_pemilik' => $ruangan,
                'jenis_tindakan' => $log->jenis_tindakan ?? 'Perbaikan',
                'bulan_pelaporan' => $bulanPelaporan,
                'tanggal_mulai' => $log->tanggal_mulai ? $log->tanggal_mulai->format('Y-m-d') : ($log->created_at ? $log->created_at->format('Y-m-d') : '-'),
                'tanggal_selesai' => $log->tanggal_selesai ? $log->tanggal_selesai->format('Y-m-d') : 'Dalam Proses',
                'durasi_pengerjaan' => $log->durasi_pengerjaan ?? '1 Hari',
                'pelaksana_vendor' => $log->pelaksana_vendor ?: 'Teknisi Elektromedis RS',
                'deskripsi_kerusakan' => $log->deskripsi_kerusakan ?: '-',
                'tindakan_perbaikan' => $log->tindakan_perbaikan ?: '-',
                'status_hasil' => $log->status_hasil ?: 'Proses',
            ];
        });

        // 3. Kalibrasi Data
        $kalibrasiData = $alkesItems->map(function ($item, $index) {
            $ruangPemilik = $item->ruangan->nama_ruangan ?? '-';
            $lokasiFisik = $item->lokasiRuangan->nama_ruangan ?? $ruangPemilik;

            $bulanKalibrasi = $item->tanggal_kalibrasi_terakhir 
                ? Carbon::parse($item->tanggal_kalibrasi_terakhir)->translatedFormat('F Y') 
                : '-';

            return [
                'no' => $index + 1,
                'nama_barang' => $item->nama_barang,
                'merk' => $item->merk ?: '-',
                'tipe' => $item->tipe ?: '-',
                'seri_number' => $item->nomor_seri ?: '-',
                'ruang_pemilik' => $ruangPemilik,
                'lokasi_alkes' => $lokasiFisik,
                'status_kalibrasi' => $item->status_kalibrasi ?: 'BELUM DIKALIBRASI',
                'bulan_kalibrasi' => $bulanKalibrasi,
                'tanggal_kalibrasi_terakhir' => $item->tanggal_kalibrasi_terakhir ? $item->tanggal_kalibrasi_terakhir->format('Y-m-d') : 'Belum ada data',
                'tanggal_kalibrasi_berikutnya' => $item->tanggal_kalibrasi_berikutnya ? $item->tanggal_kalibrasi_berikutnya->format('Y-m-d') : 'Belum dijadwalkan',
                'status_sertifikat' => $item->sertifikat_kalibrasi ? 'Ada Dokumen' : 'Belum Ada',
                'keterangan' => $item->keterangan ?: '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'updated_at' => now()->toDateTimeString(),
            'data' => [
                'alkes' => $alkesData,
                'pemeliharaan' => $pemeliharaanData,
                'kalibrasi' => $kalibrasiData,
            ]
        ]);
    }

    public function apiPemeliharaan()
    {
        $logItems = LogPemeliharaan::with(['alkes.ruangan', 'alkes.lokasiRuangan'])
            ->orderBy('id', 'desc')
            ->get();

        $data = $logItems->map(function ($log, $index) {
            $alkes = $log->alkes;
            $ruangan = $alkes->ruangan->nama_ruangan ?? '-';
            return [
                'no' => $index + 1,
                'kode_inventaris' => $alkes->kode_inventaris ?? '-',
                'nama_barang' => $alkes->nama_barang ?? '-',
                'ruang_pemilik' => $ruangan,
                'jenis_tindakan' => $log->jenis_tindakan ?? 'Perbaikan',
                'tanggal_mulai' => $log->tanggal_mulai ? $log->tanggal_mulai->format('Y-m-d H:i') : ($log->created_at ? $log->created_at->format('Y-m-d H:i') : '-'),
                'tanggal_selesai' => $log->tanggal_selesai ? $log->tanggal_selesai->format('Y-m-d H:i') : 'Dalam Proses',
                'durasi_pengerjaan' => $log->durasi_pengerjaan ?? '-',
                'pelaksana_vendor' => $log->pelaksana_vendor ?: 'Teknisi Elektromedis RS',
                'deskripsi_kerusakan' => $log->deskripsi_kerusakan ?: '-',
                'tindakan_perbaikan' => $log->tindakan_perbaikan ?: '-',
                'biaya' => (float) ($log->biaya ?? 0),
                'status_hasil' => $log->status_hasil ?: 'Proses',
            ];
        });

        return response()->json([
            'status' => 'success',
            'total' => count($data),
            'updated_at' => now()->toDateTimeString(),
            'data' => $data,
        ]);
    }

    public function apiKalibrasi()
    {
        $alkesItems = Alkes::with(['ruangan', 'lokasiRuangan'])
            ->leftJoin('ruangan', 'alkes.ruangan_id', '=', 'ruangan.id')
            ->orderBy('ruangan.nama_ruangan', 'asc')
            ->orderBy('alkes.nama_barang', 'asc')
            ->select('alkes.*')
            ->get();

        $data = $alkesItems->map(function ($item, $index) {
            $ruangPemilik = $item->ruangan->nama_ruangan ?? '-';
            $lokasiFisik = $item->lokasiRuangan->nama_ruangan ?? $ruangPemilik;
            $merk = $item->merk ?: '-';
            $tipe = $item->tipe ?: '-';
            $merkTipe = ($merk !== '-' || $tipe !== '-') ? trim("{$merk} / {$tipe}", ' /') : '-';

            return [
                'no' => $index + 1,
                'kode_inventaris' => $item->kode_inventaris ?? '-',
                'nama_barang' => $item->nama_barang,
                'merk_tipe' => $merkTipe,
                'nomor_seri' => $item->nomor_seri ?: '-',
                'ruang_pemilik' => $ruangPemilik,
                'lokasi_fisik' => $lokasiFisik,
                'status_kalibrasi' => $item->status_kalibrasi ?: 'BELUM DIKALIBRASI',
                'tanggal_kalibrasi_terakhir' => $item->tanggal_kalibrasi_terakhir ? $item->tanggal_kalibrasi_terakhir->format('Y-m-d') : 'Belum ada data',
                'tanggal_kalibrasi_berikutnya' => $item->tanggal_kalibrasi_berikutnya ? $item->tanggal_kalibrasi_berikutnya->format('Y-m-d') : 'Belum dijadwalkan',
                'status_sertifikat' => $item->sertifikat_kalibrasi ? 'Ada Dokumen' : 'Belum Ada',
                'keterangan' => $item->keterangan ?: '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'total' => count($data),
            'updated_at' => now()->toDateTimeString(),
            'data' => $data,
        ]);
    }

    public function apiHandler(Request $request)
    {
        if ($request->isMethod('post') || $request->has('data')) {
            return $this->apiSync($request);
        }
        return $this->apiIndex();
    }

    public function apiSync(Request $request, AlkesSyncService $syncService)
    {
        if ($request->isMethod('get') && !$request->has('data')) {
            return $this->apiIndex();
        }

        $secretKey = (string) config('zapin.api_key', env('ZAPIN_API_KEY', 'zapin_secret_key_rsjko_2026'));
        $authHeader = (string) ($request->header('X-ZAPIN-KEY') ?: $request->input('api_key', ''));

        if ($authHeader === '' || !hash_equals($secretKey, $authHeader)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Kunci API ZAPIN tidak valid.'
            ], 401);
        }

        $items = $request->input('data', []);
        if (empty($items) || !is_array($items)) {
            return $this->apiIndex();
        }

        $result = $syncService->sync($items);

        return response()->json([
            'status' => 'success',
            'message' => "Sinkronisasi berhasil! {$result['created_count']} data baru ditambahkan, {$result['updated_count']} data diperbarui.",
            'created_count' => $result['created_count'],
            'updated_count' => $result['updated_count'],
            'results' => $result['results'],
        ]);
    }
}
