<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanAlkes extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_alkes';
    protected $fillable = [
        'alkes_id',
        'ruangan_peminjam_id',
        'peminjam_nama',
        'tanggal_pinjam',
        'estimasi_kembali',
        'tanggal_dikembalikan',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'datetime',
        'estimasi_kembali' => 'datetime',
        'tanggal_dikembalikan' => 'datetime',
    ];

    public function alkes(): BelongsTo
    {
        return $this->belongsTo(Alkes::class, 'alkes_id');
    }

    public function ruanganPeminjam(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_peminjam_id');
    }
}
