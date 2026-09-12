<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Enums\StatusAlkes;
use App\Enums\KondisiAlkes;
use App\Models\Alkes;
use App\Models\Ruangan;
use App\Models\LogPemeliharaan;
use App\Models\PeminjamanAlkes;
use App\Models\MutasiAlkes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

$passed = 0;
$failed = 0;

function assertCondition($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failed++;
    }
}

echo "\n======================================================\n";
echo "       MEMULAI VERIFIKASI REVISI SISTEM ZAPIN        \n";
echo "======================================================\n\n";

// -----------------------------------------------------------------
// Test 1: Proteksi API (Unauthorized vs Authorized)
// -----------------------------------------------------------------
echo "1. Menguji Proteksi Autentikasi API (/api/*):\n";

$requestNoKey = Request::create('/api/ruangan', 'GET');
$responseNoKey = $kernel->handle($requestNoKey);
assertCondition(
    "Request /api/ruangan tanpa API Key mengembalikan HTTP 401",
    $responseNoKey->getStatusCode() === 401
);
$kernel->terminate($requestNoKey, $responseNoKey);

$requestBadKey = Request::create('/api/ruangan', 'GET', [], [], [], [
    'HTTP_X-ZAPIN-KEY' => 'kunci_palsu_random_123'
]);
$responseBadKey = $kernel->handle($requestBadKey);
assertCondition(
    "Request /api/ruangan dengan API Key tidak valid mengembalikan HTTP 401",
    $responseBadKey->getStatusCode() === 401
);
$kernel->terminate($requestBadKey, $responseBadKey);

$validKey = config('zapin.api_key');
$requestValidKey = Request::create('/api/ruangan', 'GET', [], [], [], [
    'HTTP_X-ZAPIN-KEY' => $validKey
]);
$responseValidKey = $kernel->handle($requestValidKey);
assertCondition(
    "Request /api/ruangan dengan X-ZAPIN-KEY valid mengembalikan HTTP 200",
    $responseValidKey->getStatusCode() === 200
);
$kernel->terminate($requestValidKey, $responseValidKey);

// -----------------------------------------------------------------
// Test 2: Proteksi Rute & Kontrol Akses Peran
// -----------------------------------------------------------------
echo "\n2. Menguji Kontrol Akses & Pembatasan Peran (RBAC):\n";

$checkRole = new \App\Http\Middleware\CheckRole();

// Uji peran 'ruangan' mencoba akses operasi khusus 'elektromedis'
$reqRoleTest1 = Request::create('/pemeliharaan/999/selesai', 'POST');
$app->instance('request', $reqRoleTest1);
session(['user_role' => 'ruangan']);
$respRole1 = $checkRole->handle($reqRoleTest1, fn() => response('OK'), 'elektromedis');

assertCondition(
    "Peran 'ruangan' diblokir (redirect ke dashboard dengan error) saat memanggil rute role:elektromedis",
    $respRole1->isRedirect(route('dashboard')) && session('error') !== null
);

// Uji peran 'tata_usaha' mencoba akses operasi modifikasi 'role:elektromedis,ruangan'
$reqRoleTest2 = Request::create('/mutasi', 'POST');
$app->instance('request', $reqRoleTest2);
session(['user_role' => 'tata_usaha']);
$respRole2 = $checkRole->handle($reqRoleTest2, fn() => response('OK'), 'elektromedis', 'ruangan');

assertCondition(
    "Peran 'tata_usaha' (Read-Only) diblokir saat memanggil rute role:elektromedis,ruangan",
    $respRole2->isRedirect(route('dashboard')) && session('error') !== null
);

// -----------------------------------------------------------------
// Test 3: Logika Medis - Pemisahan Kalibrasi dari Perbaikan Fisik
// -----------------------------------------------------------------
echo "\n3. Menguji Integritas Logika Medis & Kalibrasi:\n";

// Ruangan Uji Coba
$ruangTesting = Ruangan::firstOrCreate(
    ['nama_ruangan' => 'Unit Uji Coba'],
    ['kode_ruangan' => 'R-UJI-COBA']
);

$ruangLain = Ruangan::firstOrCreate(
    ['nama_ruangan' => 'Unit Uji Coba 2'],
    ['kode_ruangan' => 'R-UJI-COBA-2']
);

// Buat unit alat uji coba
$testAlkes = Alkes::create([
    'kode_inventaris' => 'ALT-TEST-' . time(),
    'nama_barang' => 'EKG Monitor Test Unit',
    'ruangan_id' => $ruangTesting->id,
    'lokasi_ruangan_id' => $ruangTesting->id,
    'status' => StatusAlkes::TERSEDIA->value,
    'kondisi' => KondisiAlkes::BAIK->value,
    'status_kalibrasi' => 'BELUM DIKALIBRASI',
    'tanggal_kalibrasi_terakhir' => null,
    'tanggal_kalibrasi_berikutnya' => null,
]);

// Buat log pemeliharaan aktif
$testLog = LogPemeliharaan::create([
    'alkes_id' => $testAlkes->id,
    'jenis_tindakan' => 'Perbaikan (Korektif)',
    'tanggal_mulai' => now(),
    'pelaksana_vendor' => 'Teknisi Elektromedis RS',
    'deskripsi_kerusakan' => 'Kabel power putus',
    'status_hasil' => 'Proses',
]);

$testAlkes->update(['status' => StatusAlkes::DALAM_PERBAIKAN->value]);

// Simulasikan penyelesaian perbaikan oleh Elektromedis
$controllerPemeliharaan = new \App\Http\Controllers\LogPemeliharaanController();
$requestResolve = \App\Http\Requests\ResolvePemeliharaanRequest::create("/pemeliharaan/{$testLog->id}/selesai", 'POST', [
    'diagnosa_kerusakan' => 'Sekering putus telah diganti',
    'tindakan_perbaikan' => 'Penggantian fuse sekering 2A',
    'pelaksana_vendor' => 'Teknisi Elektromedis RS',
    'biaya' => 25000,
]);
$requestResolve->setContainer($app);
$requestResolve->validateResolved();

// Jalankan resolve
$controllerPemeliharaan->resolve($requestResolve, $testLog->id);

$testAlkes->refresh();
assertCondition(
    "Unit selesai perbaikan kembali ke status 'tersedia' dan kondisi 'baik'",
    $testAlkes->status->value === StatusAlkes::TERSEDIA->value && $testAlkes->kondisi->value === KondisiAlkes::BAIK->value
);

assertCondition(
    "Status kalibrasi TIDAK berubah otomatis menjadi SUDAH DIKALIBRASI (tetap BELUM DIKALIBRASI)",
    $testAlkes->status_kalibrasi === 'BELUM DIKALIBRASI' && $testAlkes->tanggal_kalibrasi_berikutnya === null
);

// -----------------------------------------------------------------
// Test 4: Modul Peminjaman & Proteksi Double Return
// -----------------------------------------------------------------
echo "\n4. Menguji Modul Peminjaman & Proteksi Concurrency:\n";

$controllerPinjam = new \App\Http\Controllers\PeminjamanAlkesController();

// Lakukan peminjaman alat
$reqPinjam = \App\Http\Requests\StorePeminjamanRequest::create('/peminjaman', 'POST', [
    'alkes_id' => $testAlkes->id,
    'ruangan_peminjam_id' => $ruangLain->id,
    'peminjam_nama' => 'Dr. Testing Sp.A',
    'tanggal_pinjam' => now()->format('Y-m-d H:i:s'),
    'estimasi_kembali' => now()->addDays(2)->format('Y-m-d H:i:s'),
    'keterangan' => 'Keperluan tindakan darurat',
]);
$reqPinjam->setContainer($app);
$reqPinjam->validateResolved();

$controllerPinjam->store($reqPinjam);
$testAlkes->refresh();

assertCondition(
    "Alat berhasil dipinjam dan status berubah menjadi 'dipinjam'",
    $testAlkes->status->value === StatusAlkes::DIPINJAM->value
);

// Dapatkan ID peminjaman yang baru dibuat
$peminjamanActive = PeminjamanAlkes::where('alkes_id', $testAlkes->id)->where('status', 'Dipinjam')->first();
assertCondition("Record peminjaman aktif tercatat di database", $peminjamanActive !== null);

// Kembalikan alat
$reqKembalikan = Request::create("/peminjaman/{$peminjamanActive->id}/kembalikan", 'POST');
$controllerPinjam->kembalikan($reqKembalikan, $peminjamanActive->id);

$testAlkes->refresh();
$peminjamanActive->refresh();

assertCondition(
    "Alat berhasil dikembalikan dan status kembali 'tersedia'",
    $testAlkes->status->value === StatusAlkes::TERSEDIA->value && $peminjamanActive->status === 'Dikembalikan'
);

// Coba lakukan pengembalian kedua kali pada record yang sama (Double Return test)
$doubleReturnCaught = false;
try {
    $controllerPinjam->kembalikan($reqKembalikan, $peminjamanActive->id);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 422) {
        $doubleReturnCaught = true;
    }
}
assertCondition(
    "Pengembalian ganda (double return) berhasil dicegah (HTTP 422)",
    $doubleReturnCaught === true
);

// -----------------------------------------------------------------
// Test 5: Akurasi Metrik Dashboard
// -----------------------------------------------------------------
echo "\n5. Menguji Akurasi Metrik Dashboard:\n";

$dbTersedia = Alkes::where('status', StatusAlkes::TERSEDIA->value)->count();
$dashboardCtrl = new \App\Http\Controllers\DashboardController();
$dashView = $dashboardCtrl->index();
$viewData = $dashView->getData();

assertCondition(
    "Kalkulasi alkesTersedia di DashboardController sesuai persis dengan count DB status tersedia",
    $viewData['alkesTersedia'] === $dbTersedia
);

// -----------------------------------------------------------------
// Test 6: Early Warning System (EWS) Scheduler Command
// -----------------------------------------------------------------
echo "\n6. Menguji Eksekusi Command EWS Kalibrasi:\n";

$exitCodeEws = \Illuminate\Support\Facades\Artisan::call('ews:check-kalibrasi');
$outputEws = \Illuminate\Support\Facades\Artisan::output();
assertCondition(
    "Command 'ews:check-kalibrasi' berjalan sukses tanpa exception",
    $exitCodeEws === 0
);

// Cleanup testing records
$testLog->delete();
$peminjamanActive->delete();
$testAlkes->delete();

echo "\n======================================================\n";
echo "             RINGKASAN HASIL VERIFIKASI               \n";
echo "======================================================\n";
echo "Total Uji Coba: " . ($passed + $failed) . "\n";
echo "Berhasil (PASS): {$passed}\n";
echo "Gagal   (FAIL): {$failed}\n";
echo "======================================================\n\n";

exit($failed > 0 ? 1 : 0);

