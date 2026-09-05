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
        $h30 = now()->addDays(30)->toDateString();
        $h7 = now()->addDays(7)->toDateString();

        $alkesPeringatan = Alkes::whereIn('tanggal_kalibrasi_berikutnya', [$h30, $h7])->get();

        if ($alkesPeringatan->isEmpty()) {
            $this->info('Tidak ada alkes yang mendekati batas kalibrasi hari ini.');
            return;
        }

        $targetEmail = config('zapin.ews_email', 'kepala.elektromedis@rsjko.local');

        foreach ($alkesPeringatan as $alkes) {
            $sisaHari = Carbon::parse($alkes->tanggal_kalibrasi_berikutnya)->diffInDays(now());
            
            // Format Hari (7 atau 30)
            $labelHari = $sisaHari <= 8 ? '7' : '30';

            // Cegah duplikasi notifikasi di hari yang sama
            $alreadyNotified = Notification::where('alkes_id', $alkes->id)
                ->where('tipe', 'peringatan_kalibrasi')
                ->whereDate('created_at', now()->toDateString())
                ->where('judul', 'like', "%H-{$labelHari}%")
                ->exists();

            if ($alreadyNotified) {
                $this->line("Notifikasi H-{$labelHari} untuk {$alkes->nama_barang} sudah terkirim hari ini, melewati...");
                continue;
            }

            // Buat notifikasi di Dashboard
            Notification::create([
                'alkes_id' => $alkes->id,
                'ruangan_asal_id' => $alkes->ruangan_id,
                'judul' => "Peringatan Kalibrasi H-{$labelHari} ({$alkes->nama_barang})",
                'pesan' => "Alat {$alkes->nama_barang} (SN: " . ($alkes->nomor_seri ?: '-') . ") masa kalibrasinya akan habis pada " . Carbon::parse($alkes->tanggal_kalibrasi_berikutnya)->format('d M Y') . ". Harap segera jadwalkan kalibrasi.",
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
