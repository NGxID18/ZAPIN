<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'ruangan';

    protected $fillable = [
        'kode_ruangan',
        'nama_ruangan',
        'penanggung_jawab',
    ];

    public function alkes(): HasMany
    {
        return $this->hasMany(Alkes::class, 'ruangan_id');
    }

    public function alkesLokasi(): HasMany
    {
        return $this->hasMany(Alkes::class, 'lokasi_ruangan_id');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanAlkes::class, 'ruangan_peminjam_id');
    }
}
