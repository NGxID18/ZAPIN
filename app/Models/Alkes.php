<?php

namespace App\Models;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alkes extends Model
{
    use HasFactory;

    protected $table = 'alkes';

    protected $fillable = [
        'kode_inventaris',
        'nama_barang',
        'nomenklatur_id',
        'merk',
        'tipe',
        'nomor_seri',
        'tahun_pengadaan',
        'jumlah',
        'cara_perolehan',
        'nilai_perolehan',
        'ruangan_id',
        'lokasi_ruangan_id',
        'lokasi_saat_ini_note',
        'status',
        'kondisi',
        'status_kalibrasi',
        'aspak_status',
        'kib_status',
        'tanggal_kalibrasi_terakhir',
        'tanggal_kalibrasi_berikutnya',
        'sertifikat_kalibrasi',
        'sertifikat_kalibrasi_history',
        'foto_alat',
        'keterangan',
    ];

    protected $casts = [
        'status' => StatusAlkes::class,
        'kondisi' => KondisiAlkes::class,
        'jumlah' => 'integer',
        'nilai_perolehan' => 'decimal:2',
        'kib_status' => 'boolean',
        'tanggal_kalibrasi_terakhir' => 'date',
        'tanggal_kalibrasi_berikutnya' => 'date',
        'sertifikat_kalibrasi_history' => 'array',
    ];

    public function getStatusEnumAttribute(): StatusAlkes
    {
        if ($this->status instanceof StatusAlkes) {
            return $this->status;
        }
        return StatusAlkes::tryFrom($this->status) ?? StatusAlkes::TERSEDIA;
    }

    public function getKondisiEnumAttribute(): KondisiAlkes
    {
        if ($this->kondisi instanceof KondisiAlkes) {
            return $this->kondisi;
        }
        return KondisiAlkes::tryFrom($this->kondisi) ?? KondisiAlkes::BAIK;
    }

    public function nomenklatur(): BelongsTo
    {
        return $this->belongsTo(Nomenklatur::class, 'nomenklatur_id');
    }

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

    public function scopeSearch($query, $search)
    {
        $escaped = addcslashes($search, '%_');

        return $query->where(function ($q) use ($escaped) {
            $q->where('nama_barang', 'like', "%{$escaped}%")
              ->orWhere('merk', 'like', "%{$escaped}%")
              ->orWhere('tipe', 'like', "%{$escaped}%")
              ->orWhere('nomor_seri', 'like', "%{$escaped}%")
              ->orWhere('tahun_pengadaan', 'like', "%{$escaped}%")
              ->orWhere('jumlah', 'like', "%{$escaped}%")
              ->orWhere('lokasi_saat_ini_note', 'like', "%{$escaped}%")
              ->orWhere('kondisi', 'like', "%{$escaped}%")
              ->orWhere('aspak_status', 'like', "%{$escaped}%")
              ->orWhere('keterangan', 'like', "%{$escaped}%")
              ->orWhereHas('ruangan', function ($rq) use ($escaped) {
                  $rq->where('nama_ruangan', 'like', "%{$escaped}%")
                    ->orWhere('kode_ruangan', 'like', "%{$escaped}%");
              });
        });
    }

    public function scopeAccessibleByCurrentRole($query)
    {
        if (session('user_role') === 'ruangan' && session('user_ruangan_id')) {
            $userRuanganId = (int) session('user_ruangan_id');
            return $query->where(function ($q) use ($userRuanganId) {
                $q->where('ruangan_id', $userRuanganId)
                  ->orWhere('lokasi_ruangan_id', $userRuanganId);
            });
        }
        return $query;
    }

    public static function generateKodeInventaris(string $prefix = 'ALT'): string
    {
        $year = date('Y');
        $basePrefix = "{$prefix}-{$year}-";

        $maxExisting = self::where('kode_inventaris', 'like', "{$basePrefix}%")
            ->orderBy('id', 'desc')
            ->value('kode_inventaris');

        $nextNum = 1;
        if ($maxExisting && preg_match('/-(\d+)$/', $maxExisting, $matches)) {
            $nextNum = ((int) $matches[1]) + 1;
        } else {
            $count = (int) self::where('kode_inventaris', 'like', "{$basePrefix}%")->count();
            $nextNum = $count + 1;
        }

        do {
            $candidate = $basePrefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
            $nextNum++;
        } while (self::where('kode_inventaris', $candidate)->exists());

        return $candidate;
    }
}
