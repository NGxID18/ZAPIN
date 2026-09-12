<?php

namespace App\Console\Commands;

use App\Mail\EwsKalibrasiMail;
use App\Models\Alkes;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckKalibrasiEWS extends Command
{
    protected $signature = 'ews:check-kalibrasi';
    protected $description = 'Cek batas masa kalibrasi alkes untuk H-30 dan H-7';

    public function handle()
    {
        $today = now()->toDateString();
        $maxWindow = now()->addDays(30)->toDateString();

        $alkesPeringatan = Alkes::whereNotNull('tanggal_kalibrasi_berikutnya')
            ->where('tanggal_kalibrasi_berikutnya', '>=', $today)
            ->where('tanggal_kalibrasi_berikutnya', '<=', $maxWindow)
            ->get();

        if ($alkesPeringatan->isEmpty()) {
            $this->info('Tidak ada alkes yang mendekati batas kalibrasi hari ini.');
            return;
        }

        $targetEmail = config('zapin.ews_email', 'kepala.elektromedis@rsjko.local');

        foreach ($alkesPeringatan as $alkes) {
            $tglTarget = Carbon::parse($alkes->tanggal_kalibrasi_berikutnya)->startOfDay();
            $sisaHari = (int) now()->startOfDay()->diffInDays($tglTarget, false);

            if ($sisaHari < 0) {
                continue;
            }

            // Tentukan kategori jendela peringatan (H-7 atau H-30)
            if ($sisaHari <= 7) {
                $labelHari = '7';
                $dedupDays = 7;
            } else {
                $labelHari = '30';
                $dedupDays = 30;
            }

            // Cegah duplikasi notifikasi dalam jendela waktu yang sama
            $alreadyNotified = Notification::where('alkes_id', $alkes->id)
                ->where('tipe', 'peringatan_kalibrasi')
                ->where('judul', 'like', "%H-{$labelHari}%")
                ->where('created_at', '>=', now()->subDays($dedupDays))
                ->exists();

            if ($alreadyNotified) {
                $this->line("Notifikasi H-{$labelHari} untuk {$alkes->nama_barang} sudah terkirim dalam {$dedupDays} hari terakhir, melewati...");
                continue;
            }

            // Buat notifikasi di Dashboard
            Notification::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $alkes->ruangan_id,
                'judul' => "Peringatan Kalibrasi H-{$labelHari} ({$alkes->nama_barang})",
                'pesan' => "Alat {$alkes->nama_barang} (SN: " . ($alkes->nomor_seri ?: '-') . ") masa kalibrasinya akan habis pada " . Carbon::parse($alkes->tanggal_kalibrasi_berikutnya)->format('d M Y') . " (Sisa {$sisaHari} hari). Harap segera jadwalkan kalibrasi.",
                'tipe' => 'peringatan_kalibrasi',
            ]);

            // Coba kirim email secara graceful
            if (!empty($targetEmail)) {
                try {
                    Mail::to($targetEmail)->send(new EwsKalibrasiMail($alkes, $labelHari));
                    $this->info("Email EWS berhasil dikirim untuk alat: {$alkes->nama_barang} ke {$targetEmail}");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("EWS Mail Warning (SN: {$alkes->nomor_seri}): " . $e->getMessage());
                    $this->warn("Gagal mengirim email untuk alat: {$alkes->nama_barang}. (Akan dicatat di log)");
                }
            }
        }

        $this->info('Pengecekan EWS Kalibrasi Selesai.');
    }
}
