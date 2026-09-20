<?php

namespace App\Enums;

enum StatusAlkes: string
{
    case TERSEDIA = 'Tersedia';
    case DIPINJAM = 'Dipinjam';
    case DALAM_PERBAIKAN = 'Dalam Perbaikan';

    public static function fromRaw(?string $value): self
    {
        $val = trim($value ?? '');
        return match ($val) {
            'Dipinjam' => self::DIPINJAM,
            'Dalam Perbaikan' => self::DALAM_PERBAIKAN,
            default => self::TERSEDIA,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TERSEDIA => 'Tersedia',
            self::DIPINJAM => 'Dipinjam',
            self::DALAM_PERBAIKAN => 'Dalam Perbaikan',
        };
    }

    public function warnaBadge(): string
    {
        return match ($this) {
            self::TERSEDIA => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::DIPINJAM => 'bg-blue-100 text-blue-800 border-blue-300',
            self::DALAM_PERBAIKAN => 'bg-amber-100 text-amber-800 border-amber-300',
        };
    }
}

