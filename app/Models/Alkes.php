<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alkes extends Model
{
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

    public function getKondisiEnumAttribute(): object
    {
        $val = trim($this->kondisi ?? '');
        $upper = strtoupper($val);

        if (str_contains($upper, 'BERAT')) {
            return new class($val) {
                public function __construct(private string $val) {}
                public function label(): string { return $this->val ?: 'Rusak Berat'; }
                public function warnaBadge(): string { return 'bg-rose-100 text-rose-800 border-rose-300'; }
            };
        }

        if (str_contains($upper, 'RINGAN')) {
            return new class($val) {
                public function __construct(private string $val) {}
                public function label(): string { return $this->val ?: 'Rusak Ringan'; }
                public function warnaBadge(): string { return 'bg-amber-100 text-amber-800 border-amber-300'; }
            };
        }

        if ($upper === 'BAIK') {
            return new class($val) {
                public function __construct(private string $val) {}
                public function label(): string { return 'Baik'; }
                public function warnaBadge(): string { return 'bg-emerald-100 text-emerald-800 border-emerald-300'; }
            };
        }

        // Jika kondisi kosong di spreadsheet
        return new class($val) {
            public function __construct(private string $val) {}
            public function label(): string { return '-'; }
            public function warnaBadge(): string { return 'bg-slate-100 text-slate-600 border-slate-200'; }
        };
    }

    public function getStatusEnumAttribute(): object
    {
        $status = $this->status ?? 'Tersedia';

        if ($status === 'Dipinjam') {
            return new class {
                public function label(): string { return 'Dipinjam'; }
                public function warnaBadge(): string { return 'bg-blue-100 text-blue-800 border-blue-300'; }
            };
        }

        if ($status === 'Dalam Perbaikan') {
            return new class {
                public function label(): string { return 'Dalam Perbaikan'; }
                public function warnaBadge(): string { return 'bg-amber-100 text-amber-800 border-amber-300'; }
            };
        }

        return new class {
            public function label(): string { return 'Tersedia'; }
            public function warnaBadge(): string { return 'bg-emerald-100 text-emerald-800 border-emerald-300'; }
        };
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
}
