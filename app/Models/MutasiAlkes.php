<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiAlkes extends Model
{
    protected $table = 'mutasi_alkes';

    protected $fillable = [
        'alkes_id',
        'ruangan_asal_id',
        'ruangan_tujuan_id',
        'pemohon',
        'penanggung_jawab',
        'alasan_mutasi',
        'tanggal_mutasi',
        'status',
    ];

    protected $casts = [
        'tanggal_mutasi' => 'datetime',
    ];

    public function alkes(): BelongsTo
    {
        return $this->belongsTo(Alkes::class, 'alkes_id');
    }

    public function ruanganAsal(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_asal_id');
    }

    public function ruanganTujuan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_tujuan_id');
    }
}

