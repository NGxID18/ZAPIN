<?php

namespace App\Mail;

use App\Models\Alkes;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EwsKalibrasiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alkes;
    public $hariTersisa;

    public function __construct(Alkes $alkes, $hariTersisa)
    {
        $this->alkes = $alkes;
        $this->hariTersisa = $hariTersisa;
    }

    public function build()
    {
        return $this->subject("[ZAPIN EWS] Peringatan Kalibrasi H-{$this->hariTersisa}: {$this->alkes->nama_barang}")
                    ->view('emails.ews-kalibrasi');
    }
}
