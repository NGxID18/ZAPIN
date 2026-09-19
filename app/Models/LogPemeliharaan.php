<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPemeliharaan extends Model
{
    protected $table = 'log_pemeliharaan';

    protected $fillable = [
        'alkes_id',
        'jenis_tindakan',
        'tanggal_mulai',
        'tanggal_selesai',
        'pelaksana_vendor',
        'deskripsi_kerusakan',
        'tindakan_perbaikan',
        'biaya',
        'status_hasil',
        'foto_kerusakan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'biaya' => 'decimal:2',
    ];

    public function alkes(): BelongsTo
    {
        return $this->belongsTo(Alkes::class, 'alkes_id');
    }
}

