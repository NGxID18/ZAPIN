<?php

namespace App\Models;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alkes extends Model
{
    use SoftDeletes;

    protected $table = 'alkes';

    protected $fillable = [
        'no_urut',
        'kode_inventaris',
        'nama_barang',
        'merk',
        'tipe',
        'nomor_seri',
        'tahun',
        'jumlah',
        'cara_perolehan',
        'nilai_perolehan',
        'distributor',
        'ruangan_id',
        'lokasi_ruangan_id',
        'lokasi_saat_ini_note',
        'kondisi',
        'status',
        'aspak',
        'kib',
        'non_kib_dan_aspak',
        'akl_akd',
        'status_kalibrasi',
        'tanggal_kalibrasi_terakhir',
        'tanggal_kalibrasi_berikutnya',
        'sertifikat_kalibrasi',
        'sertifikat_kalibrasi_history',
        'keterangan',
    ];

    protected $casts = [
        'no_urut' => 'integer',
        'jumlah' => 'integer',
        'tanggal_kalibrasi_terakhir' => 'date',
        'tanggal_kalibrasi_berikutnya' => 'date',
        'sertifikat_kalibrasi_history' => 'array',
    ];

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    public function lokasiRuangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'lokasi_ruangan_id');
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiAlkes::class, 'alkes_id')->latest();
    }

    public function logPemeliharaan(): HasMany
    {
        return $this->hasMany(LogPemeliharaan::class, 'alkes_id')->latest();
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanAlkes::class, 'alkes_id')->latest();
    }

    public function getIsDipindahkanAttribute(): bool
    {
        return $this->ruangan_id !== $this->lokasi_ruangan_id;
    }

    public function getTahunPengadaanAttribute(): ?string
    {
        return $this->tahun;
    }

    public function getKondisiEnumAttribute(): KondisiAlkes
    {
        return KondisiAlkes::fromRaw($this->kondisi);
    }

    public function getStatusEnumAttribute(): StatusAlkes
    {
        return StatusAlkes::fromRaw($this->status);
    }

    public function scopeAccessibleByCurrentRole($query)
    {
        // Seluruh ruangan berhak melihat (read/browse) data alkes di semua ruangan rumah sakit.
        return $query;
    }

    /**
     * Memeriksa apakah pengguna saat ini berhak mengelola penuh (Edit / Update / Hapus) alkes ini.
     */
    public function canBeManagedByCurrentRole(): bool
    {
        $role = session('user_role');
        if ($role === 'elektromedis') {
            return true;
        }
        if ($role === 'ruangan' && session('user_ruangan_id')) {
            return (int) $this->ruangan_id === (int) session('user_ruangan_id');
        }
        return false;
    }

    /**
     * Memeriksa apakah pengguna saat ini berhak mengoperasikan alkes ini (Pindah Ruangan / Lapor Perbaikan).
     */
    public function canBeOperatedByCurrentRole(): bool
    {
        $role = session('user_role');
        if ($role === 'elektromedis') {
            return true;
        }
        if ($role === 'ruangan' && session('user_ruangan_id')) {
            $myRoom = (int) session('user_ruangan_id');
            return (int) $this->ruangan_id === $myRoom || (int) $this->lokasi_ruangan_id === $myRoom;
        }
        return false;
    }
}
