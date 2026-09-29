<?php

namespace Tests\Feature;

use App\Models\Ruangan;
use Tests\TestCase;

class AlkesWorkflowTest extends TestCase
{
    public function test_api_ping_returns_ok(): void
    {
        $response = $this->get('/api/ping');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'ZAPIN API ready',
        ]);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_accessible_with_session_role(): void
    {
        $response = $this->withSession([
            'user_role' => 'elektromedis',
            'user_role_label' => 'Instalasi Elektromedis',
            'user_ruangan_id' => 1,
            'user_ruangan_name' => 'Elektromedis',
        ])->get('/');

        $response->assertStatus(200);
        $response->assertSee('ZAPIN');
        $response->assertSee('Total Unit Alkes');
        $response->assertSee('Alkes Kondisi Baik');
        $response->assertSee('Alkes Rusak / Perbaikan');
        $response->assertSee('Nilai Perolehan & Sumber Pengadaan', false);
        $response->assertSee('Alkes dengan Nilai Tertinggi');
    }

    public function test_alkes_index_accessible_with_session(): void
    {
        $response = $this->withSession([
            'user_role' => 'elektromedis',
            'user_role_label' => 'Instalasi Elektromedis',
            'user_ruangan_id' => 1,
            'user_ruangan_name' => 'Elektromedis',
        ])->get('/alkes');

        $response->assertStatus(200);
        $response->assertSee('Inventaris Alkes');

        // Verify all 17 spreadsheet columns appear in the table
        $response->assertSee('No.');
        $response->assertSee('Nama Barang');
        $response->assertSee('Merk');
        $response->assertSee('Tipe');
        $response->assertSee('Serial Number');
        $response->assertSee('Tahun');
        $response->assertSee('Cara Perolehan');
        $response->assertSee('Nilai Perolehan');
        $response->assertSee('Distributor');
        $response->assertSee('Ruangan');
        $response->assertSee('Lokasi Saat Ini');
        $response->assertSee('Kondisi Alat');
        $response->assertSee('ASPAK');
        $response->assertSee('KIB');
        $response->assertSee('NON KIB dan ASPAK');
        $response->assertSee('AKL/AKD');
        $response->assertSee('Keterangan');
    }

    public function test_alkes_show_displays_all_spreadsheet_columns(): void
    {
        $alkes = \App\Models\Alkes::first();
        if (!$alkes) {
            $this->markTestSkipped('No alkes record found.');
        }

        $response = $this->withSession([
            'user_role' => 'elektromedis',
            'user_role_label' => 'Instalasi Elektromedis',
        ])->get('/alkes/' . $alkes->id);

        $response->assertStatus(200);
        $response->assertSee('No. Urut');
        $response->assertSee('Nama Barang');
        $response->assertSee('Merk / Produsen');
        $response->assertSee('Model / Tipe');
        $response->assertSee('Nomor Seri');
        $response->assertSee('Tahun');
        $response->assertSee('Cara Perolehan');
        $response->assertSee('Nilai Perolehan');
        $response->assertSee('Distributor');
        $response->assertSee('Status ASPAK');
        $response->assertSee('Status KIB');
        $response->assertSee('NON KIB dan ASPAK');
        $response->assertSee('Izin Edar (AKL / AKD)');
        $response->assertSee('Keterangan / Catatan Inventaris');
    }

    public function test_alkes_create_and_edit_contains_all_spreadsheet_fields(): void
    {
        $alkes = \App\Models\Alkes::first();

        $createRes = $this->withSession([
            'user_role' => 'elektromedis',
        ])->get('/alkes/create');

        $createRes->assertStatus(200);
        $createRes->assertSee('name="cara_perolehan"', false);
        $createRes->assertSee('name="nilai_perolehan"', false);
        $createRes->assertSee('name="distributor"', false);
        $createRes->assertSee('name="non_kib_dan_aspak"', false);
        $createRes->assertSee('name="akl_akd"', false);

        if ($alkes) {
            $editRes = $this->withSession([
                'user_role' => 'elektromedis',
            ])->get('/alkes/' . $alkes->id . '/edit');

            $editRes->assertStatus(200);
            $editRes->assertSee('name="cara_perolehan"', false);
            $editRes->assertSee('name="nilai_perolehan"', false);
            $editRes->assertSee('name="distributor"', false);
            $editRes->assertSee('name="non_kib_dan_aspak"', false);
            $editRes->assertSee('name="akl_akd"', false);
        }
    }

    public function test_peminjaman_index_accessible(): void
    {
        $response = $this->withSession([
            'user_role' => 'elektromedis',
            'user_role_label' => 'Instalasi Elektromedis',
            'user_ruangan_id' => 1,
            'user_ruangan_name' => 'Elektromedis',
        ])->get('/peminjaman');

        $response->assertStatus(200);
        $response->assertSee('Peminjaman Alat');
    }

    public function test_kalibrasi_index_accessible(): void
    {
        $response = $this->withSession([
            'user_role' => 'elektromedis',
            'user_role_label' => 'Instalasi Elektromedis',
            'user_ruangan_id' => 1,
            'user_ruangan_name' => 'Elektromedis',
        ])->get('/kalibrasi');

        $response->assertStatus(200);
        $response->assertSee('Kalibrasi', false);
    }

    public function test_pemeliharaan_index_accessible(): void
    {
        $response = $this->withSession([
            'user_role' => 'elektromedis',
            'user_role_label' => 'Instalasi Elektromedis',
            'user_ruangan_id' => 1,
            'user_ruangan_name' => 'Elektromedis',
        ])->get('/pemeliharaan');

        $response->assertStatus(200);
        $response->assertSee('Perbaikan', false);
    }

    public function test_certificate_serve_rejects_invalid_extension(): void
    {
        $response = $this->withSession([
            'user_role' => 'elektromedis',
        ])->get('/database/sertifikat/malicious.php');

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Format dokumen tidak diizinkan.');
    }

    public function test_mutasi_rejected_for_unauthorized_room(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $ruanganA = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang A Testing', 'kode_ruangan' => 'R-TEST-A']);
        $ruanganB = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang B Testing', 'kode_ruangan' => 'R-TEST-B']);
        $ruanganC = Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang C Testing', 'kode_ruangan' => 'R-TEST-C']);

        $alkes = \App\Models\Alkes::create([
            'no_urut' => 999991,
            'nama_barang' => 'Tensimeter Test',
            'ruangan_id' => $ruanganA->id,
            'lokasi_ruangan_id' => $ruanganA->id,
            'status' => 'Tersedia',
        ]);

        // Ruang C tries to mutate device in Ruang A
        $response = $this->withSession([
            'user_role' => 'ruangan',
            'user_ruangan_id' => $ruanganC->id,
            'user_ruangan_name' => 'Ruang C Testing',
        ])->post('/mutasi', [
            'alkes_id' => $alkes->id,
            'ruangan_tujuan_id' => $ruanganB->id,
            'pemohon' => 'Staff C',
            'penanggung_jawab' => 'PJ C',
            'alasan_mutasi' => 'Testing unauthorized mutation',
        ]);

        $response->assertStatus(403);

        // Cleanup
        $alkes->forceDelete();
    }
}

