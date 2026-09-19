<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruangan extends Model
{
    protected $table = 'ruangan';

    protected $fillable = [
        'nama_ruangan',
        'kode_ruangan',
    ];

    public function alkes(): HasMany
    {
        return $this->hasMany(Alkes::class, 'ruangan_id');
    }

    public function alkesLokasi(): HasMany
    {
        return $this->hasMany(Alkes::class, 'lokasi_ruangan_id');
    }
}

