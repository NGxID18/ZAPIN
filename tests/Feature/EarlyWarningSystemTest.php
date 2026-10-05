<?php

namespace Tests\Feature;

use App\Models\Alkes;
use App\Models\Notification;
use App\Models\Ruangan;
use App\Services\EarlyWarningService;
use Carbon\Carbon;
use Tests\TestCase;

class EarlyWarningSystemTest extends TestCase
{
    protected Ruangan $ruangan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ruangan = Ruangan::firstOrCreate(
            ['kode_ruangan' => 'R-TEST-EWS'],
            ['nama_ruangan' => 'Ruang Uji EWS']
        );
    }

    public function test_alkes_triggers_h30_notification_when_deadline_within_30_days(): void
    {
        $today = Carbon::parse('2026-10-05');
        Carbon::setTestNow($today);

        // Alkes dengan tenggat 25 hari ke depan (tahap H-30)
        $alkes = Alkes::create([
            'no_urut' => 9901,
            'nama_barang' => 'Pasien Monitor EWS H30',
            'merk' => 'Mindray',
            'ruangan_id' => $this->ruangan->id,
            'status_kalibrasi' => 'SUDAH DIKALIBRASI',
            'tanggal_kalibrasi_terakhir' => '2025-10-30',
            'tanggal_kalibrasi_berikutnya' => '2026-10-30', // 25 hari dari 2026-10-05
        ]);

        $service = app(EarlyWarningService::class);
        $result = $service->checkAndGenerateNotifications($today);

        $this->assertGreaterThanOrEqual(1, $result['h30_created']);

        $notif = Notification::where('alkes_id', $alkes->id)
            ->where('stage', 'H-30')
            ->where('target_date', '2026-10-30')
            ->first();

        $this->assertNotNull($notif);
        $this->assertEquals('ews_kalibrasi', $notif->type);
        $this->assertEquals('warning', $notif->level);
        $this->assertFalse($notif->is_read);
        $this->assertStringContainsString('EWS H-30', $notif->judul);
        $this->assertStringContainsString('Pasien Monitor EWS H30', $notif->pesan);

        // Pastikan idempotensi: menjalankan ulang tidak menduplikasi notifikasi H-30
        $resultRepeat = $service->checkAndGenerateNotifications($today);
        $this->assertEquals(0, $resultRepeat['h30_created']);
        $this->assertEquals(1, Notification::where('alkes_id', $alkes->id)->where('stage', 'H-30')->count());

        Carbon::setTestNow();
    }

    public function test_single_alkes_triggers_two_notifications_h30_and_then_h7_when_h30_ignored(): void
    {
        $startDate = Carbon::parse('2026-10-01');
        Carbon::setTestNow($startDate);

        $targetDate = '2026-10-25'; // Jatuh tempo 24 hari dari 1 Okt (H-30)

        $alkes = Alkes::create([
            'no_urut' => 9902,
            'nama_barang' => 'Defibrillator EWS Dua Tahap',
            'merk' => 'Nihon Kohden',
            'ruangan_id' => $this->ruangan->id,
            'status_kalibrasi' => 'SUDAH DIKALIBRASI',
            'tanggal_kalibrasi_terakhir' => '2025-10-25',
            'tanggal_kalibrasi_berikutnya' => $targetDate,
        ]);

        $service = app(EarlyWarningService::class);

        // Tahap 1: Pada 1 Oktober (24 hari sebelum jatuh tempo), harus menghasilkan notifikasi H-30
        $res1 = $service->checkAndGenerateNotifications($startDate);
        $this->assertGreaterThanOrEqual(1, $res1['h30_created']);

        $notifH30 = Notification::where('alkes_id', $alkes->id)
            ->where('stage', 'H-30')
            ->first();
        $this->assertNotNull($notifH30);
        $this->assertEquals('H-30', $notifH30->stage);

        // Skenario: Notifikasi H-30 terabaikan atau ditandai dibaca oleh elektromedis
        $notifH30->markAsRead();
        $this->assertTrue($notifH30->fresh()->is_read);

        // Waktu bergulir ke 20 Oktober (5 hari sebelum jatuh tempo, masuk tahap H-7)
        $h7Date = Carbon::parse('2026-10-20');
        Carbon::setTestNow($h7Date);

        // Tahap 2: Harus menghasilkan notifikasi KEDUA yaitu H-7 (kritis)
        $res2 = $service->checkAndGenerateNotifications($h7Date);
        $this->assertGreaterThanOrEqual(1, $res2['h7_created']);

        $notifH7 = Notification::where('alkes_id', $alkes->id)
            ->where('stage', 'H-7')
            ->first();
        $this->assertNotNull($notifH7);
        $this->assertEquals('H-7', $notifH7->stage);
        $this->assertEquals('danger', $notifH7->level);
        $this->assertFalse($notifH7->is_read); // Notifikasi H-7 muncul baru sebagai unread
        $this->assertStringContainsString('EWS H-7', $notifH7->judul);

        // Verifikasi: Alkes ini sekarang memiliki tepat 2 notifikasi (H-30 dan H-7)
        $totalNotifs = Notification::where('alkes_id', $alkes->id)->count();
        $this->assertEquals(2, $totalNotifs);

        // Menjalankan pengecekan ulang di periode H-7 tidak menduplikasi notifikasi H-7
        $res2Repeat = $service->checkAndGenerateNotifications($h7Date);
        $this->assertEquals(0, $res2Repeat['h7_created']);
        $this->assertEquals(2, Notification::where('alkes_id', $alkes->id)->count());

        Carbon::setTestNow();
    }

    public function test_recalibration_resets_cycle_for_new_target_date(): void
    {
        $today = Carbon::parse('2026-10-05');
        Carbon::setTestNow($today);

        $alkes = Alkes::create([
            'no_urut' => 9903,
            'nama_barang' => 'Infusion Pump Kalibrasi Ulang',
            'ruangan_id' => $this->ruangan->id,
            'status_kalibrasi' => 'SUDAH DIKALIBRASI',
            'tanggal_kalibrasi_terakhir' => '2025-10-10',
            'tanggal_kalibrasi_berikutnya' => '2026-10-10', // H-5 (H-7 stage)
        ]);

        $service = app(EarlyWarningService::class);
        $service->checkAndGenerateNotifications($today);

        $this->assertEquals(1, Notification::where('alkes_id', $alkes->id)->where('stage', 'H-7')->count());

        // Elektromedis melakukan kalibrasi dan memperbarui tanggal berikutnya ke tahun depan
        $alkes->update([
            'tanggal_kalibrasi_terakhir' => '2026-10-06',
            'tanggal_kalibrasi_berikutnya' => '2027-10-06',
        ]);

        // Cek kembali: sekarang alkes valid dan tidak menghasilkan notifikasi baru untuk hari ini
        $res = $service->checkAndGenerateNotifications($today);
        $this->assertEquals(0, $res['h7_created']);

        // Tetapi ketika waktu bergulir mendekati 2027-10-06 (misal H-20 di tahun 2027)
        $nextYearH30 = Carbon::parse('2027-09-20');
        $resNextYear = $service->checkAndGenerateNotifications($nextYearH30);
        $this->assertGreaterThanOrEqual(1, $resNextYear['h30_created']);

        $notifNewCycle = Notification::where('alkes_id', $alkes->id)
            ->where('stage', 'H-30')
            ->where('target_date', '2027-10-06')
            ->first();
        $this->assertNotNull($notifNewCycle);

        Carbon::setTestNow();
    }

    public function test_mark_all_notifications_as_read_via_route(): void
    {
        Notification::create([
            'type' => 'ews_kalibrasi',
            'target_role' => 'elektromedis',
            'stage' => 'H-30',
            'target_date' => '2026-11-01',
            'judul' => 'Uji Notifikasi Unread 1',
            'pesan' => 'Pesan 1',
            'is_read' => false,
        ]);

        Notification::create([
            'type' => 'ews_kalibrasi',
            'target_role' => 'elektromedis',
            'stage' => 'H-7',
            'target_date' => '2026-10-08',
            'judul' => 'Uji Notifikasi Unread 2',
            'pesan' => 'Pesan 2',
            'is_read' => false,
        ]);

        $this->assertGreaterThanOrEqual(2, Notification::unread()->forRole('elektromedis')->count());

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->withSession(['user_role' => 'elektromedis'])
            ->post(route('notifications.read-all'));

        $response->assertStatus(302);

        $this->assertEquals(0, Notification::unread()->forRole('elektromedis')->count());
    }

    public function test_console_command_ews_check_kalibrasi_executes_successfully(): void
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('ews:check-kalibrasi');
        $this->assertEquals(0, $exitCode);
    }

    public function test_kalibrasi_index_filters_h7_and_h30(): void
    {
        $today = Carbon::parse('2026-10-05');
        Carbon::setTestNow($today);

        Alkes::create([
            'no_urut' => 9910,
            'nama_barang' => 'Alkes Filter H7',
            'ruangan_id' => $this->ruangan->id,
            'status_kalibrasi' => 'SUDAH DIKALIBRASI',
            'tanggal_kalibrasi_berikutnya' => '2026-10-10', // 5 hari lagi (H-7)
        ]);

        Alkes::create([
            'no_urut' => 9911,
            'nama_barang' => 'Alkes Filter H30',
            'ruangan_id' => $this->ruangan->id,
            'status_kalibrasi' => 'SUDAH DIKALIBRASI',
            'tanggal_kalibrasi_berikutnya' => '2026-10-25', // 20 hari lagi (H-30)
        ]);

        // Uji filter H-7
        $resH7 = $this->withSession(['user_role' => 'elektromedis'])
            ->get(route('kalibrasi.index', ['status_kalibrasi' => 'H-7']));
        $resH7->assertStatus(200);
        $itemsH7 = $resH7->viewData('alkesList')->pluck('nama_barang')->all();
        $this->assertContains('Alkes Filter H7', $itemsH7);
        $this->assertNotContains('Alkes Filter H30', $itemsH7);

        // Uji filter H-30
        $resH30 = $this->withSession(['user_role' => 'elektromedis'])
            ->get(route('kalibrasi.index', ['status_kalibrasi' => 'H-30']));
        $resH30->assertStatus(200);
        $itemsH30 = $resH30->viewData('alkesList')->pluck('nama_barang')->all();
        $this->assertContains('Alkes Filter H30', $itemsH30);
        $this->assertNotContains('Alkes Filter H7', $itemsH30);

        Carbon::setTestNow();
    }
}
