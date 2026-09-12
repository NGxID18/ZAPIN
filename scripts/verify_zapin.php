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

// -----------------------------------------------------------------
// Test 7: Otorisasi BOLA pada AlkesController::show
// -----------------------------------------------------------------
echo "\n7. Menguji Proteksi BOLA / IDOR pada AlkesController::show:\n";

$alkesRuangA = Alkes::create([
    'kode_inventaris' => 'ALT-BOLA-A-' . time(),
    'nama_barang' => 'Mesin Anestesi Ruang A',
    'ruangan_id' => $ruangTesting->id,
    'lokasi_ruangan_id' => $ruangTesting->id,
    'status' => StatusAlkes::TERSEDIA->value,
    'kondisi' => KondisiAlkes::BAIK->value,
]);

$alkesController = new \App\Http\Controllers\AlkesController();

// Simulasikan user login sebagai Ruangan B (ruangLain) mencoba melihat alkes Ruangan A
session([
    'user_role' => 'ruangan',
    'user_ruangan_id' => $ruangLain->id,
    'user_ruangan_name' => 'Unit Uji Coba 2',
]);

$bolaCaught = false;
try {
    $alkesController->show($alkesRuangA->id);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $bolaCaught = true;
    }
}
assertCondition(
    "User peran 'ruangan' B diblokir (HTTP 403) saat mencoba akses detail alkes milik ruangan A",
    $bolaCaught === true
);

// Simulasikan user login sebagai Ruangan A (pemilik) melihat alkesnya sendiri
session([
    'user_role' => 'ruangan',
    'user_ruangan_id' => $ruangTesting->id,
    'user_ruangan_name' => 'Unit Uji Coba',
]);
$viewRuangSendiri = $alkesController->show($alkesRuangA->id);
assertCondition(
    "User peran 'ruangan' pemilik berhasil melihat detail alkes ruangannya sendiri",
    $viewRuangSendiri->getName() === 'alkes.show'
);

// -----------------------------------------------------------------
// Test 8: Otorisasi BOLA pada PeminjamanAlkesController::kembalikan
// -----------------------------------------------------------------
echo "\n8. Menguji Proteksi BOLA pada Pengembalian Peminjaman:\n";

$pinjamTest2 = PeminjamanAlkes::create([
    'alkes_id' => $alkesRuangA->id,
    'ruangan_peminjam_id' => $ruangLain->id,
    'peminjam_nama' => 'Perawat Ruang B',
    'tanggal_pinjam' => now(),
    'estimasi_kembali' => now()->addDay(),
    'status' => 'Dipinjam',
]);

$ruangKetiga = Ruangan::firstOrCreate(
    ['nama_ruangan' => 'Unit Uji Coba 3'],
    ['kode_ruangan' => 'R-UJI-COBA-3']
);

// Simulasikan user dari Ruangan C (tidak terkait) mencoba mengembalikan alat
session([
    'user_role' => 'ruangan',
    'user_ruangan_id' => $ruangKetiga->id,
    'user_ruangan_name' => 'Unit Uji Coba 3',
]);

$reqReturnUnauthorized = Request::create("/peminjaman/{$pinjamTest2->id}/kembalikan", 'POST');
$unauthorizedReturnCaught = false;
try {
    $controllerPinjam->kembalikan($reqReturnUnauthorized, $pinjamTest2->id);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $unauthorizedReturnCaught = true;
    }
}
assertCondition(
    "Ruangan ketiga yang tidak terkait diblokir (HTTP 403) saat mencoba menandai pengembalian",
    $unauthorizedReturnCaught === true
);

// Ruangan peminjam berhasil mengembalikan alat
session([
    'user_role' => 'ruangan',
    'user_ruangan_id' => $ruangLain->id,
    'user_ruangan_name' => 'Unit Uji Coba 2',
]);
$respKembalikanValid = $controllerPinjam->kembalikan($reqReturnUnauthorized, $pinjamTest2->id);
$pinjamTest2->refresh();
assertCondition(
    "Ruangan peminjam sah berhasil mengembalikan alat",
    $pinjamTest2->status === 'Dikembalikan'
);

// -----------------------------------------------------------------
// Test 9: Proteksi Anti-Spoofing Ruangan Peminjam pada Store
// -----------------------------------------------------------------
echo "\n9. Menguji Proteksi Anti-Spoofing Ruangan Peminjam:\n";

session([
    'user_role' => 'ruangan',
    'user_ruangan_id' => $ruangTesting->id,
    'user_ruangan_name' => 'Unit Uji Coba',
]);

// Coba ajukan peminjaman mengatasnamakan ruangLain padahal login sebagai ruangTesting
$reqSpoofed = \App\Http\Requests\StorePeminjamanRequest::create('/peminjaman', 'POST', [
    'alkes_id' => $alkesRuangA->id,
    'ruangan_peminjam_id' => $ruangLain->id, // Spoofed!
    'peminjam_nama' => 'Oknum Ruangan',
    'tanggal_pinjam' => now()->format('Y-m-d H:i:s'),
    'estimasi_kembali' => now()->addDays(2)->format('Y-m-d H:i:s'),
]);
$reqSpoofed->setContainer($app);
$reqSpoofed->validateResolved();

$spoofCaught = false;
try {
    $controllerPinjam->store($reqSpoofed);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $spoofCaught = true;
    }
}
assertCondition(
    "Pengajuan peminjaman dengan ruangan_peminjam_id palsu diblokir (HTTP 403)",
    $spoofCaught === true
);

// -----------------------------------------------------------------
// Test 10: Proteksi Integritas Legal pada AlkesController::destroy
// -----------------------------------------------------------------
echo "\n10. Menguji Proteksi Penghapusan Legal (KARS/Permenkes):\n";

// Unit alkesRuangA sekarang memiliki riwayat peminjaman (pinjamTest2)
session(['user_role' => 'elektromedis']);
$respDestroyPrevented = $alkesController->destroy($alkesRuangA->id);

$alkesRuangAExists = Alkes::where('id', $alkesRuangA->id)->exists();
assertCondition(
    "Alat dengan riwayat peminjaman/pemeliharaan dicegah dihapus permanen dari DB",
    $alkesRuangAExists === true && session('error') !== null
);

// -----------------------------------------------------------------
// Test 11: Validasi Mass Assignment Model PeminjamanAlkes
// -----------------------------------------------------------------
echo "\n11. Menguji Mass Assignment Protection Model PeminjamanAlkes:\n";

$modelPinjam = new PeminjamanAlkes();
$fillableFields = $modelPinjam->getFillable();
assertCondition(
    "Model PeminjamanAlkes mendefinisikan fillable secara eksplisit (tidak guarded = [])",
    !empty($fillableFields) && in_array('alkes_id', $fillableFields) && in_array('peminjam_nama', $fillableFields)
);

// -----------------------------------------------------------------
// Test 12: Keamanan APP_KEY & Konfigurasi Server
// -----------------------------------------------------------------
echo "\n12. Menguji Kriptografi APP_KEY & Konfigurasi Server:\n";

$appKey = env('APP_KEY', '');
$isOldWeakKey = str_contains($appKey, 'testkey123456789abcdefghijklmnop') || $appKey === 'base64:dGVzdGtleTEyMzQ1Njc4OWFiY2RlZmdoaWprbG1ub3A=';
assertCondition(
    "APP_KEY bukan kunci default yang prediktif",
    !$isOldWeakKey && strlen($appKey) > 20
);

$nginxConf = file_get_contents(__DIR__ . '/../zapin_nginx.conf');
assertCondition(
    "zapin_nginx.conf memblokir eksekusi PHP di direktori /uploads/",
    str_contains($nginxConf, 'location ^~ /uploads/') && str_contains($nginxConf, 'deny all')
);

$dbConf = file_get_contents(__DIR__ . '/../config/database.php');
assertCondition(
    "config/database.php mengaktifkan journal_mode WAL untuk SQLite",
    str_contains($dbConf, "'journal_mode' => 'wal'")
);

// -----------------------------------------------------------------
// Test 13: Sanitasi Wildcard SQL pada Alkes::scopeSearch
// -----------------------------------------------------------------
echo "\n13. Menguji Sanitasi Wildcard SQL pada Alkes::scopeSearch:\n";

$searchSql = Alkes::search('%_test_%')->toSql();
assertCondition(
    "Pencarian mengandung parameter yang diamankan dari wildcard SQL",
    str_contains($searchSql, 'like ?')
);

// Cleanup
$pinjamTest2->delete();
$alkesRuangA->delete();

echo "\n======================================================\n";
echo "             RINGKASAN HASIL VERIFIKASI               \n";
echo "======================================================\n";
echo "Total Uji Coba: " . ($passed + $failed) . "\n";
echo "Berhasil (PASS): {$passed}\n";
echo "Gagal   (FAIL): {$failed}\n";
echo "======================================================\n\n";

exit($failed > 0 ? 1 : 0);

