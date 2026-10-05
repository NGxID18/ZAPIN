<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'alkes_id',
        'target_role',
        'judul',
        'pesan',
        'stage',
        'target_date',
        'level',
        'url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'target_date' => 'date',
    ];

    public function alkes(): BelongsTo
    {
        return $this->belongsTo(Alkes::class, 'alkes_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('target_role', $role);
    }

    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function badgeClasses(): string
    {
        return match ($this->stage) {
            'H-7' => 'bg-rose-100 text-rose-800 border-rose-300',
            'H-30' => 'bg-amber-100 text-amber-900 border-amber-300',
            'EXPIRED' => 'bg-red-100 text-red-900 border-red-300',
            default => match ($this->level) {
                'danger' => 'bg-rose-100 text-rose-800 border-rose-300',
                'warning' => 'bg-amber-100 text-amber-900 border-amber-300',
                default => 'bg-blue-100 text-blue-800 border-blue-300',
            },
        };
    }
}
