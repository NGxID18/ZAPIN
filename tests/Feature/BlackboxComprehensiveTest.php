<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\LogPemeliharaan;
use App\Models\MutasiAlkes;
use App\Models\PeminjamanAlkes;
use App\Models\Ruangan;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlackboxComprehensiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Http::fake([
            '*' => Http::response(['status' => 'success'], 200),
        ]);
    }

    public function test_auth_login_successful_for_all_three_roles(): void
    {
        $ruangan = Ruangan::first();
        $password = (string) config('zapin.default_password', '1234');

        // 1. Elektromedis
        $res1 = $this->post('/login', [
            'role' => 'elektromedis',
            'password' => $password,
        ]);
        $res1->assertRedirect('/');
        $this->assertEquals('elektromedis', session('user_role'));

        // 2. Tata Usaha
        $res2 = $this->post('/login', [
            'role' => 'tata_usaha',
            'password' => $password,
        ]);
        $res2->assertRedirect('/');
        $this->assertEquals('tata_usaha', session('user_role'));

        // 3. Ruangan
        $res3 = $this->post('/login', [
            'role' => 'ruangan',
            'ruangan_id' => $ruangan->id,
            'password' => $password,
        ]);
        $res3->assertRedirect('/');
        $this->assertEquals('ruangan', session('user_role'));
        $this->assertEquals($ruangan->id, session('user_ruangan_id'));
    }

    public function test_auth_login_fails_with_invalid_credentials(): void
    {
        // Wrong password
        $res1 = $this->post('/login', [
            'role' => 'elektromedis',
            'password' => 'wrongpassword',
        ]);
        $res1->assertSessionHas('error', 'Kata sandi salah.');

        // Missing ruangan_id for ruangan role
        $res2 = $this->post('/login', [
            'role' => 'ruangan',
            'password' => (string) config('zapin.default_password', '1234'),
        ]);
        $res2->assertSessionHasErrors(['ruangan_id']);
    }

    public function test_auth_logout_clears_session(): void
    {
        $this->withSession(['user_role' => 'elektromedis'])->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertNull(session('user_role'));
    }

    public function test_role_permissions_tata_usaha_read_only(): void
    {
        $session = [
            'user_role' => 'tata_usaha',
            'user_role_label' => 'Tata Usaha / Direksi (Read-Only)',
        ];

        // Allowed GET routes
        $this->withSession($session)->get('/')->assertStatus(200);
        $this->withSession($session)->get('/alkes')->assertStatus(200);
        $this->withSession($session)->get('/mutasi')->assertStatus(200);
        $this->withSession($session)->get('/pemeliharaan')->assertStatus(200);
        $this->withSession($session)->get('/peminjaman')->assertStatus(200);
        $this->withSession($session)->get('/kalibrasi')->assertStatus(200);
        $this->withSession($session)->get('/ruangan')->assertStatus(200);
        $this->withSession($session)->get('/activity-logs')->assertStatus(200);

        // Disallowed Mutation routes (Should redirect to dashboard with error)
        $this->withSession($session)->get('/alkes/create')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->withSession($session)->post('/alkes', ['nama_barang' => 'Illegal'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->withSession($session)->get('/mutasi/buat')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->withSession($session)->post('/mutasi', [])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->withSession($session)->post('/peminjaman', [])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->withSession($session)->get('/pemeliharaan/buat')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->withSession($session)->post('/pemeliharaan', [])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_role_permissions_ruangan_ownership_enforcement(): void
    {
        $ruangA = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang Tes Alpha', 'kode_ruangan' => 'R-ALPHA']);
        $ruangB = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang Tes Beta', 'kode_ruangan' => 'R-BETA']);

        $alkesA = Alkes::create([
            'no_urut' => 999901,
            'nama_barang' => 'Alkes Ruang Alpha',
            'ruangan_id' => $ruangA->id,
            'lokasi_ruangan_id' => $ruangA->id,
            'status' => 'Tersedia',
        ]);

        // Ruang Beta tries to edit alkes belonging to Ruang Alpha
        $res = $this->withSession([
            'user_role' => 'ruangan',
            'user_ruangan_id' => $ruangB->id,
            'user_ruangan_name' => 'Ruang Tes Beta',
        ])->get('/alkes/' . $alkesA->id . '/edit');

        $res->assertRedirect(route('alkes.show', $alkesA->id));
        $res->assertSessionHas('error');

        // Cleanup
        $alkesA->forceDelete();
    }

    public function test_dashboard_metrics_and_accuracy(): void
    {
        $res = $this->withSession([
            'user_role' => 'elektromedis',
        ])->get('/');

        $res->assertStatus(200);
        $totalAlkes = Alkes::count();
        $res->assertSee((string) $totalAlkes);
    }

    public function test_alkes_index_search_filter_sort_and_pagination(): void
    {
        $session = ['user_role' => 'elektromedis'];

        // 1. Search
        $resSearch = $this->withSession($session)->get('/alkes?search=USG');
        $resSearch->assertStatus(200);

        // 2. Filter by ruangan
        $ruang = Ruangan::first();
        $resFilter = $this->withSession($session)->get('/alkes?ruangan_id=' . $ruang->id);
        $resFilter->assertStatus(200);

        // 3. Filter by kondisi
        $resKondisi = $this->withSession($session)->get('/alkes?kondisi=BAIK');
        $resKondisi->assertStatus(200);

        // 4. Sort
        $resSort = $this->withSession($session)->get('/alkes?sort_by=nama_barang&sort_dir=desc');
        $resSort->assertStatus(200);

        // 5. Pagination all
        $resAll = $this->withSession($session)->get('/alkes?per_page=all');
        $resAll->assertStatus(200);
    }

    public function test_alkes_crud_lifecycle(): void
    {
        $ruang = Ruangan::first();
        $session = ['user_role' => 'elektromedis'];

        // 1. CREATE
        $createRes = $this->withSession($session)->post('/alkes', [
            'nama_barang' => 'Test Unit Defibrillator Blackbox',
            'merk' => 'Philips Test',
            'tipe' => 'HeartStart XL',
            'nomor_seri' => 'SN-BB-9999',
            'tahun_pengadaan' => '2024',
            'jumlah' => 1,
            'cara_perolehan' => 'APBD',
            'nilai_perolehan' => '85.000.000',
            'distributor' => 'PT Medika Jaya',
            'ruangan_id' => $ruang->id,
            'kondisi' => 'BAIK',
            'aspak' => 'TERDATA',
            'kib' => 'TERDATA',
            'non_kib_dan_aspak' => 'Ada Bukti Serah Terima',
            'akl_akd' => 'AKL 20501812345',
            'keterangan' => 'Unit uji coba blackbox testing',
        ]);

        $createRes->assertRedirect();
        $alkes = Alkes::where('nomor_seri', 'SN-BB-9999')->first();
        $this->assertNotNull($alkes);
        $this->assertEquals('Test Unit Defibrillator Blackbox', $alkes->nama_barang);
        $this->assertEquals('TERDATA', $alkes->aspak);
        $this->assertEquals('TERDATA', $alkes->kib);

        // 2. READ / SHOW
        $showRes = $this->withSession($session)->get('/alkes/' . $alkes->id);
        $showRes->assertStatus(200);
        $showRes->assertSee('Test Unit Defibrillator Blackbox');
        $showRes->assertSee('SN-BB-9999');

        // 3. UPDATE
        $updateRes = $this->withSession($session)->put('/alkes/' . $alkes->id, [
            'nama_barang' => 'Test Unit Defibrillator Updated',
            'merk' => 'Philips Pro',
            'tipe' => 'HeartStart XL2',
            'nomor_seri' => 'SN-BB-9999',
            'tahun_pengadaan' => '2024',
            'cara_perolehan' => 'APBD',
            'nilai_perolehan' => '90.000.000',
            'distributor' => 'PT Medika Utama',
            'ruangan_id' => $ruang->id,
            'kondisi' => 'RUSAK RINGAN',
            'aspak' => 'TERDATA',
            'kib' => 'TERDATA',
            'keterangan' => 'Catatan revisi blackbox testing',
        ]);

        $updateRes->assertRedirect(route('alkes.show', $alkes->id));
        $alkes->refresh();
        $this->assertEquals('Test Unit Defibrillator Updated', $alkes->nama_barang);
        $this->assertEquals('RUSAK RINGAN', $alkes->kondisi);

        // 4. DELETE (Soft delete)
        $delRes = $this->withSession($session)->delete('/alkes/' . $alkes->id);
        $delRes->assertRedirect(route('alkes.index'));
        $this->assertSoftDeleted('alkes', ['id' => $alkes->id]);

        // Cleanup
        $alkes->forceDelete();
    }

    public function test_alkes_create_multiple_units(): void
    {
        $ruang = Ruangan::first();
        $session = ['user_role' => 'elektromedis'];

        $res = $this->withSession($session)->post('/alkes', [
            'nama_barang' => 'Stetoskop Batch Multi',
            'merk' => 'Littmann',
            'tipe' => 'Classic III',
            'tahun_pengadaan' => '2024',
            'jumlah' => 3,
            'ruangan_id' => $ruang->id,
            'kondisi' => 'BAIK',
        ]);

        $res->assertRedirect();
        $createdUnits = Alkes::where('nama_barang', 'Stetoskop Batch Multi')->get();
        $this->assertCount(3, $createdUnits);

        // Verify each has unique no_urut and jumlah is 1
        $noUruts = $createdUnits->pluck('no_urut')->toArray();
        $this->assertCount(3, array_unique($noUruts));
        foreach ($createdUnits as $unit) {
            $this->assertEquals(1, $unit->jumlah);
            $unit->forceDelete();
        }
    }

    public function test_mutasi_full_lifecycle(): void
    {
        $ruangA = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang Asal Mutasi', 'kode_ruangan' => 'R-MUT-A']);
        $ruangB = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang Tujuan Mutasi', 'kode_ruangan' => 'R-MUT-B']);

        $alkes = Alkes::create([
            'no_urut' => 999902,
            'nama_barang' => 'Alkes Uji Mutasi',
            'ruangan_id' => $ruangA->id,
            'lokasi_ruangan_id' => $ruangA->id,
            'status' => 'Tersedia',
        ]);

        // Same room should fail
        $failRes = $this->withSession(['user_role' => 'elektromedis'])->post('/mutasi', [
            'alkes_id' => $alkes->id,
            'ruangan_tujuan_id' => $ruangA->id,
            'pemohon' => 'Staff Uji',
            'penanggung_jawab' => 'PJ Uji',
            'alasan_mutasi' => 'Uji mutasi ruangan sama',
        ]);
        $failRes->assertStatus(422);

        // Valid mutation
        $successRes = $this->withSession(['user_role' => 'elektromedis'])->post('/mutasi', [
            'alkes_id' => $alkes->id,
            'ruangan_tujuan_id' => $ruangB->id,
            'pemohon' => 'Staff Uji Valid',
            'penanggung_jawab' => 'PJ Uji Valid',
            'alasan_mutasi' => 'Kebutuhan Ruang Tujuan',
        ]);
        $successRes->assertRedirect(route('mutasi.index'));

        $alkes->refresh();
        $this->assertEquals($ruangB->id, $alkes->lokasi_ruangan_id);
        $this->assertStringContainsString('Dipindahkan ke Ruang Tujuan Mutasi', $alkes->lokasi_saat_ini_note);

        // Cleanup
        MutasiAlkes::where('alkes_id', $alkes->id)->delete();
        $alkes->forceDelete();
    }

    public function test_pemeliharaan_full_lifecycle(): void
    {
        $ruang = Ruangan::first();
        $alkes = Alkes::create([
            'no_urut' => 999903,
            'nama_barang' => 'Alkes Uji Pemeliharaan',
            'ruangan_id' => $ruang->id,
            'lokasi_ruangan_id' => $ruang->id,
            'status' => 'Tersedia',
            'kondisi' => 'BAIK',
        ]);

        // 1. Report damage
        $reportRes = $this->withSession(['user_role' => 'elektromedis'])->post('/pemeliharaan', [
            'alkes_id' => $alkes->id,
            'deskripsi_kerusakan' => 'Layar tidak menyala saat dinyalakan',
            'jenis_tindakan' => 'Perbaikan Elektronik',
        ]);
        $reportRes->assertRedirect(route('pemeliharaan.index'));

        $alkes->refresh();
        $this->assertEquals('Dalam Perbaikan', $alkes->status);
        $this->assertEquals('RUSAK RINGAN', $alkes->kondisi);

        $log = LogPemeliharaan::where('alkes_id', $alkes->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Proses', $log->status_hasil);

        // 2. Resolve repair
        $resolveRes = $this->withSession(['user_role' => 'elektromedis'])->post('/pemeliharaan/' . $log->id . '/selesai', [
            'tindakan_perbaikan' => 'Penggantian power supply unit',
            'biaya' => 250000,
            'pelaksana_vendor' => 'Teknisi Elektromedis Internal',
        ]);
        $resolveRes->assertRedirect(route('pemeliharaan.index'));

        $alkes->refresh();
        $this->assertEquals('Tersedia', $alkes->status);
        $this->assertEquals('BAIK', $alkes->kondisi);

        $log->refresh();
        $this->assertEquals('Selesai', $log->status_hasil);

        // Cleanup
        $log->delete();
        $alkes->forceDelete();
    }

    public function test_peminjaman_full_lifecycle(): void
    {
        $ruangAsal = Ruangan::first();
        $ruangPeminjam = Ruangan::where('id', '!=', $ruangAsal->id)->first() ?? $ruangAsal;

        $alkes = Alkes::create([
            'no_urut' => 999904,
            'nama_barang' => 'Alkes Uji Peminjaman',
            'ruangan_id' => $ruangAsal->id,
            'lokasi_ruangan_id' => $ruangAsal->id,
            'status' => 'Tersedia',
        ]);

        // 1. Borrow
        $borrowRes = $this->withSession(['user_role' => 'elektromedis'])->post('/peminjaman', [
            'alkes_id' => $alkes->id,
            'ruangan_peminjam_id' => $ruangPeminjam->id,
            'peminjam_nama' => 'Dr. Testing Peminjaman',
            'tanggal_pinjam' => now()->toDateString(),
            'estimasi_kembali' => now()->addDays(2)->toDateString(),
            'keterangan' => 'Peminjaman darurat untuk tindakan',
        ]);
        $borrowRes->assertRedirect(route('peminjaman.index'));

        $alkes->refresh();
        $this->assertEquals('Dipinjam', $alkes->status);
        $this->assertStringContainsString('Dipinjam oleh Dr. Testing Peminjaman', $alkes->lokasi_saat_ini_note);

        $peminjaman = PeminjamanAlkes::where('alkes_id', $alkes->id)->first();
        $this->assertNotNull($peminjaman);

        // 2. Borrow again while already borrowed should fail
        $secondBorrow = $this->withSession(['user_role' => 'elektromedis'])->post('/peminjaman', [
            'alkes_id' => $alkes->id,
            'ruangan_peminjam_id' => $ruangPeminjam->id,
            'peminjam_nama' => 'Peminjam Kedua',
            'tanggal_pinjam' => now()->toDateString(),
            'estimasi_kembali' => now()->addDays(1)->toDateString(),
        ]);
        $secondBorrow->assertStatus(422);

        // 3. Return
        $returnRes = $this->withSession(['user_role' => 'elektromedis'])->post('/peminjaman/' . $peminjaman->id . '/kembalikan');
        $returnRes->assertRedirect(route('peminjaman.index'));

        $alkes->refresh();
        $this->assertEquals('Tersedia', $alkes->status);
        $this->assertNull($alkes->lokasi_saat_ini_note);

        $peminjaman->refresh();
        $this->assertEquals('Dikembalikan', $peminjaman->status);

        // 4. Return again should fail
        $doubleReturn = $this->withSession(['user_role' => 'elektromedis'])->post('/peminjaman/' . $peminjaman->id . '/kembalikan');
        $doubleReturn->assertStatus(422);

        // Cleanup
        $peminjaman->delete();
        $alkes->forceDelete();
    }

    public function test_kalibrasi_full_lifecycle(): void
    {
        $ruang = Ruangan::first();
        $alkes = Alkes::create([
            'no_urut' => 999905,
            'nama_barang' => 'Alkes Uji Kalibrasi',
            'ruangan_id' => $ruang->id,
            'lokasi_ruangan_id' => $ruang->id,
            'status' => 'Tersedia',
            'status_kalibrasi' => 'BELUM DIKALIBRASI',
        ]);

        $res = $this->withSession(['user_role' => 'elektromedis'])->post('/kalibrasi/' . $alkes->id, [
            'status_kalibrasi' => 'SUDAH DIKALIBRASI',
            'tanggal_kalibrasi_terakhir' => '2026-01-15',
            'tanggal_kalibrasi_berikutnya' => '2027-01-15',
            'keterangan' => 'Kalibrasi lolos uji akurasi',
        ]);

        $res->assertRedirect(route('kalibrasi.index'));
        $alkes->refresh();
        $this->assertEquals('SUDAH DIKALIBRASI', $alkes->status_kalibrasi);
        $this->assertEquals('2026-01-15', $alkes->tanggal_kalibrasi_terakhir->toDateString());
        $this->assertEquals('2027-01-15', $alkes->tanggal_kalibrasi_berikutnya->toDateString());

        // Cleanup
        $alkes->forceDelete();
    }

    public function test_api_export_data(): void
    {
        $secret = config('zapin.api_key');

        // Unauthorized
        $unauthRes = $this->getJson('/api/sheets/export-data');
        $unauthRes->assertStatus(401);

        $wrongSecretRes = $this->withHeaders(['X-Zapin-Secret' => 'invalid-secret'])
            ->getJson('/api/sheets/export-data');
        $wrongSecretRes->assertStatus(401);

        // Authorized via Header
        $authRes = $this->withHeaders(['X-Zapin-Secret' => $secret])
            ->getJson('/api/sheets/export-data');
        $authRes->assertStatus(200);
        $authRes->assertJsonStructure([
            'status',
            'total',
            'data' => [
                '*' => [
                    'no_urut',
                    'nama_barang',
                    'merk',
                    'tipe',
                    'nomor_seri',
                    'tahun',
                    'jumlah',
                    'cara_perolehan',
                    'nilai_perolehan',
                    'distributor',
                    'ruangan',
                    'lokasi_saat_ini',
                    'kondisi',
                    'aspak',
                    'kib',
                    'non_kib_dan_aspak',
                    'akl_akd',
                    'keterangan',
                ]
            ]
        ]);
        $this->assertEquals('success', $authRes->json('status'));
        $this->assertGreaterThan(0, $authRes->json('total'));
    }

    public function test_api_webhook_update(): void
    {
        $secret = config('zapin.api_key');

        // Unauthorized
        $unauthRes = $this->postJson('/api/sheets/webhook-update', ['action' => 'sheet_row_edited']);
        $unauthRes->assertStatus(401);

        // Create temporary test alkes
        $ruang = Ruangan::first();
        $testAlkes = Alkes::create([
            'no_urut' => 999906,
            'nama_barang' => 'Webhook Sync Test Equipment',
            'ruangan_id' => $ruang->id,
            'lokasi_ruangan_id' => $ruang->id,
            'kondisi' => 'BAIK',
            'aspak' => 'TIDAK TERDATA',
            'kib' => 'TIDAK TERDATA',
        ]);

        // Authorized update via webhook
        $payload = [
            'secret' => $secret,
            'action' => 'sheet_row_edited',
            'row_index' => 9999,
            'edited_column_name' => 'kondisi',
            'data' => [
                'no_urut' => 999906,
                'nama_barang' => 'Webhook Sync Test Equipment Updated',
                'kondisi' => 'RUSAK BERAT',
                'aspak' => 'TERDATA',
                'kib' => 'TERDATA',
            ]
        ];

        $webhookRes = $this->withHeaders(['X-Zapin-Secret' => $secret])
            ->postJson('/api/sheets/webhook-update', $payload);

        $webhookRes->assertStatus(200);
        $webhookRes->assertJson(['status' => 'success']);

        $testAlkes->refresh();
        $this->assertEquals('Webhook Sync Test Equipment Updated', $testAlkes->nama_barang);
        $this->assertEquals('RUSAK BERAT', $testAlkes->kondisi);
        $this->assertEquals('Dalam Perbaikan', $testAlkes->status);
        $this->assertEquals('TERDATA', $testAlkes->aspak);
        $this->assertEquals('TERDATA', $testAlkes->kib);

        // Cleanup
        $testAlkes->forceDelete();
    }

    public function test_concurrency_and_last_write_wins(): void
    {
        $ruang = Ruangan::first();
        $alkes = Alkes::create([
            'no_urut' => 999907,
            'nama_barang' => 'Concurrency Base Unit',
            'merk' => 'Initial Merk',
            'ruangan_id' => $ruang->id,
            'lokasi_ruangan_id' => $ruang->id,
        ]);

        // Simulate two sequential updates arriving in quick succession
        $alkes->update(['merk' => 'Merk Write A']);
        $alkes->update(['merk' => 'Merk Write B']);

        $alkes->refresh();
        // Last write wins
        $this->assertEquals('Merk Write B', $alkes->merk);

        $alkes->forceDelete();
    }
}
