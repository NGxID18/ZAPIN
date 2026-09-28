<?php

namespace Tests\Unit;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use App\Enums\UserRole;
use App\Models\Alkes;
use Tests\TestCase;

class AlkesEnumsAndModelTest extends TestCase
{
    public function test_kondisi_alkes_from_raw(): void
    {
        $this->assertSame(KondisiAlkes::BAIK, KondisiAlkes::fromRaw('BAIK'));
        $this->assertSame(KondisiAlkes::BAIK, KondisiAlkes::fromRaw('baik'));
        $this->assertSame(KondisiAlkes::RUSAK_RINGAN, KondisiAlkes::fromRaw('RUSAK RINGAN'));
        $this->assertSame(KondisiAlkes::RUSAK_BERAT, KondisiAlkes::fromRaw('RUSAK BERAT'));
        $this->assertSame(KondisiAlkes::UNKNOWN, KondisiAlkes::fromRaw(null));
        $this->assertSame(KondisiAlkes::UNKNOWN, KondisiAlkes::fromRaw('-'));
    }

    public function test_status_alkes_from_raw(): void
    {
        $this->assertSame(StatusAlkes::TERSEDIA, StatusAlkes::fromRaw('Tersedia'));
        $this->assertSame(StatusAlkes::DIPINJAM, StatusAlkes::fromRaw('Dipinjam'));
        $this->assertSame(StatusAlkes::DALAM_PERBAIKAN, StatusAlkes::fromRaw('Dalam Perbaikan'));
        $this->assertSame(StatusAlkes::TERSEDIA, StatusAlkes::fromRaw('Unknown'));
    }

    public function test_user_role_labels(): void
    {
        $this->assertSame('Instalasi Elektromedis', UserRole::ELEKTROMEDIS->label());
        $this->assertSame('Instalasi / Ruangan', UserRole::RUANGAN->label());
        $this->assertSame('Manajemen / Penunjang', UserRole::TATA_USAHA->label());
    }

    public function test_can_be_managed_by_current_role(): void
    {
        $alkes = new Alkes();
        $alkes->ruangan_id = 5;

        // When role is elektromedis
        session(['user_role' => 'elektromedis']);
        $this->assertTrue($alkes->canBeManagedByCurrentRole());

        // When role is ruangan and ID matches
        session(['user_role' => 'ruangan', 'user_ruangan_id' => 5]);
        $this->assertTrue($alkes->canBeManagedByCurrentRole());

        // When role is ruangan and ID does not match
        session(['user_role' => 'ruangan', 'user_ruangan_id' => 10]);
        $this->assertFalse($alkes->canBeManagedByCurrentRole());

        // When role is tata_usaha (read-only)
        session(['user_role' => 'tata_usaha']);
        $this->assertFalse($alkes->canBeManagedByCurrentRole());
    }

    public function test_can_be_operated_by_current_role(): void
    {
        $alkes = new Alkes();
        $alkes->ruangan_id = 5;
        $alkes->lokasi_ruangan_id = 8;

        // Elektromedis can operate any
        session(['user_role' => 'elektromedis']);
        $this->assertTrue($alkes->canBeOperatedByCurrentRole());

        // Ruangan owning the asset can operate
        session(['user_role' => 'ruangan', 'user_ruangan_id' => 5]);
        $this->assertTrue($alkes->canBeOperatedByCurrentRole());

        // Ruangan where asset currently resides can operate
        session(['user_role' => 'ruangan', 'user_ruangan_id' => 8]);
        $this->assertTrue($alkes->canBeOperatedByCurrentRole());

        // Other room cannot operate
        session(['user_role' => 'ruangan', 'user_ruangan_id' => 99]);
        $this->assertFalse($alkes->canBeOperatedByCurrentRole());
    }
}
