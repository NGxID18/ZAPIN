<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Alkes;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EarlyWarningService
{
    /**
     * Memeriksa seluruh alkes dan menerbitkan notifikasi EWS H-30 dan H-7.
     * Sebuah alkes akan menerima notifikasi 2 kali:
     * 1 kali pada tahap H-30 (30 s/d 8 hari sebelum tenggat),
     * dan 1 kali lagi pada tahap H-7 (<= 7 hari sebelum tenggat) jika belum dikalibrasi.
     */
    public function checkAndGenerateNotifications(?Carbon $referenceDate = null): array
    {
        $today = $referenceDate ? $referenceDate->copy()->startOfDay() : now()->startOfDay();

        $alkesList = Alkes::with(['ruangan', 'lokasiRuangan'])
            ->whereNotNull('tanggal_kalibrasi_berikutnya')
            ->get();

        $createdH30 = 0;
        $createdH7 = 0;
        $createdExpired = 0;

        foreach ($alkesList as $alkes) {
            $targetDate = $alkes->tanggal_kalibrasi_berikutnya?->toDateString();
            if (!$targetDate) {
                continue;
            }

            $targetCarbon = Carbon::parse($targetDate)->startOfDay();
            $daysRemaining = (int) $today->diffInDays($targetCarbon, false);
            $formattedDate = $targetCarbon->format('d/m/Y');
            $namaRuangan = $alkes->ruangan->nama_ruangan ?? 'RS';

            // Tahap 1: Peringatan H-30 (8 s/d 30 hari sebelum tenggat kalibrasi)
            if ($daysRemaining > 7 && $daysRemaining <= 30) {
                $exists = Notification::where('alkes_id', $alkes->id)
                    ->where('stage', 'H-30')
                    ->where('target_date', $targetDate)
                    ->exists();

                if (!$exists) {
                    try {
                        Notification::create([
                            'type' => 'ews_kalibrasi',
                            'alkes_id' => $alkes->id,
                            'target_role' => 'elektromedis',
                            'stage' => 'H-30',
                            'target_date' => $targetDate,
                            'level' => 'warning',
                            'judul' => "[EWS H-30] Peringatan Kalibrasi: {$alkes->nama_barang}",
                            'pesan' => "Alkes '{$alkes->nama_barang}' (Ruangan: {$namaRuangan}) jatuh tempo kalibrasi dalam {$daysRemaining} hari ({$formattedDate}). Harap segera jadwalkan pengujian kalibrasi.",
                            'url' => route('kalibrasi.index', ['search' => $alkes->nomor_seri ?: $alkes->nama_barang]),
                            'is_read' => false,
                        ]);

                        ActivityLog::record(
                            'EWS Kalibrasi H-30',
                            "Notifikasi H-30 diterbitkan untuk alkes '{$alkes->nama_barang}' (jatuh tempo {$formattedDate}, sisa {$daysRemaining} hari).",
                            $namaRuangan,
                            'Sistem EWS'
                        );

                        $createdH30++;
                    } catch (\Throwable $e) {
                        // Abaikan jika terjadi race-condition duplicate key
                    }
                }
            }

            // Tahap 2: Peringatan Kritis H-7 (0 s/d 7 hari sebelum tenggat kalibrasi)
            elseif ($daysRemaining >= 0 && $daysRemaining <= 7) {
                $exists = Notification::where('alkes_id', $alkes->id)
                    ->where('stage', 'H-7')
                    ->where('target_date', $targetDate)
                    ->exists();

                if (!$exists) {
                    try {
                        Notification::create([
                            'type' => 'ews_kalibrasi',
                            'alkes_id' => $alkes->id,
                            'target_role' => 'elektromedis',
                            'stage' => 'H-7',
                            'target_date' => $targetDate,
                            'level' => 'danger',
                            'judul' => "[EWS H-7] Kalibrasi Mendesak: {$alkes->nama_barang}",
                            'pesan' => "PERINGATAN KRITIS: Alkes '{$alkes->nama_barang}' (Ruangan: {$namaRuangan}) jatuh tempo kalibrasi dalam {$daysRemaining} hari ({$formattedDate})! Segera laksanakan kalibrasi.",
                            'url' => route('kalibrasi.index', ['search' => $alkes->nomor_seri ?: $alkes->nama_barang]),
                            'is_read' => false,
                        ]);

                        ActivityLog::record(
                            'EWS Kalibrasi H-7',
                            "Notifikasi kritis H-7 diterbitkan untuk alkes '{$alkes->nama_barang}' (jatuh tempo {$formattedDate}, sisa {$daysRemaining} hari).",
                            $namaRuangan,
                            'Sistem EWS'
                        );

                        $createdH7++;
                    } catch (\Throwable $e) {
                        // Abaikan jika terjadi race-condition duplicate key
                    }
                }
            }

            // Tahap 3: Kadaluarsa / Expired (Masa berlaku lewat hari H)
            elseif ($daysRemaining < 0) {
                $exists = Notification::where('alkes_id', $alkes->id)
                    ->where('stage', 'EXPIRED')
                    ->where('target_date', $targetDate)
                    ->exists();

                if (!$exists) {
                    try {
                        $hariLewat = abs($daysRemaining);
                        Notification::create([
                            'type' => 'ews_kalibrasi',
                            'alkes_id' => $alkes->id,
                            'target_role' => 'elektromedis',
                            'stage' => 'EXPIRED',
                            'target_date' => $targetDate,
                            'level' => 'danger',
                            'judul' => "[EWS Kadaluarsa] Masa Kalibrasi Habis: {$alkes->nama_barang}",
                            'pesan' => "PERINGATAN: Masa kalibrasi alkes '{$alkes->nama_barang}' (Ruangan: {$namaRuangan}) telah habis sejak {$formattedDate} ({$hariLewat} hari lalu). Unit tidak boleh digunakan untuk tindakan klinis sebelum dikalibrasi ulang.",
                            'url' => route('kalibrasi.index', ['search' => $alkes->nomor_seri ?: $alkes->nama_barang]),
                            'is_read' => false,
                        ]);

                        ActivityLog::record(
                            'EWS Kalibrasi Kadaluarsa',
                            "Notifikasi kadaluarsa diterbitkan untuk alkes '{$alkes->nama_barang}' (telah lewat {$hariLewat} hari dari {$formattedDate}).",
                            $namaRuangan,
                            'Sistem EWS'
                        );

                        $createdExpired++;
                    } catch (\Throwable $e) {
                        // Abaikan duplicate
                    }
                }
            }
        }

        return [
            'h30_created' => $createdH30,
            'h7_created' => $createdH7,
            'expired_created' => $createdExpired,
            'total_created' => $createdH30 + $createdH7 + $createdExpired,
        ];
    }

    /**
     * Mengambil daftar notifikasi terbaru untuk peran tertentu.
     */
    public function getRecentNotifications(string $role = 'elektromedis', int $limit = 10): Collection
    {
        return Notification::where('target_role', $role)
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Menghitung total notifikasi yang belum dibaca.
     */
    public function getUnreadCount(string $role = 'elektromedis'): int
    {
        return Notification::where('target_role', $role)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Menandai semua notifikasi untuk peran tertentu sebagai telah dibaca.
     */
    public function markAllAsRead(string $role = 'elektromedis'): int
    {
        return Notification::where('target_role', $role)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
