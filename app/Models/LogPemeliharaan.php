<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPemeliharaan extends Model
{
    use HasFactory;

    protected $table = 'log_pemeliharaan';

    protected $fillable = [
        'alkes_id',
        'jenis_tindakan',
        'tanggal_mulai',
        'tanggal_selesai',
        'pelaksana_vendor',
        'deskripsi_kerusakan',
        'foto_kerusakan',
        'tindakan_perbaikan',
        'biaya',
        'status_hasil',
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

    public function getDurasiPengerjaanAttribute(): string
    {
        $start = $this->tanggal_mulai ?: $this->created_at;
        if (!$start) return '-';

        $end = $this->tanggal_selesai ?: now();
        $totalDays = $start->diffInDays($end);

        if ($totalDays <= 1) {
            return '1 Hari';
        }

        return "{$totalDays} Hari";
    }

    public function getKpiBadgeAttribute(): array
    {
        $start = $this->tanggal_mulai ?: $this->created_at;
        if (!$start) {
            return ['label' => 'Normal', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'];
        }

        $end = $this->tanggal_selesai ?: now();
        $totalHours = $start->diffInHours($end);
        $isCompleted = ($this->status_hasil === 'Selesai');

        if ($totalHours < 24) {
            return [
                'label' => $isCompleted ? 'Sangat Cepat (<24 Jam)' : 'Proses Baru (<24 Jam)',
                'class' => 'bg-emerald-100 text-emerald-900 border-emerald-300'
            ];
        }

        if ($totalHours <= 72) {
            return [
                'label' => $isCompleted ? 'Normal (1-3 Hari)' : 'Proses Normal (1-3 Hari)',
                'class' => 'bg-teal-100 text-teal-900 border-teal-300'
            ];
        }

        if ($totalHours <= 168) {
            return [
                'label' => $isCompleted ? 'Perlu Perhatian (4-7 Hari)' : 'Proses Berjalan (4-7 Hari)',
                'class' => 'bg-amber-100 text-amber-900 border-amber-300'
            ];
        }

        return [
            'label' => $isCompleted ? 'Pengerjaan Lama (>7 Hari)' : 'Tertunda / Membutuhkan Pengawasan (>7 Hari)',
            'class' => 'bg-rose-100 text-rose-900 border-rose-300 font-black'
        ];
    }
}
