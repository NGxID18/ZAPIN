<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nomenklatur extends Model
{
    use HasFactory;

    protected $table = 'nomenklatur';

    protected $fillable = [
        'kode_nomenklatur',
        'nama_alat',
        'kategori',
        'deskripsi',
    ];

    public function alkes(): HasMany
    {
        return $this->hasMany(Alkes::class, 'nomenklatur_id');
    }
}
