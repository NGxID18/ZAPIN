<?php

namespace App\Enums;

enum KondisiAlkes: string
{
    case BAIK = 'BAIK';
    case RUSAK_RINGAN = 'RUSAK RINGAN';
    case RUSAK_BERAT = 'RUSAK BERAT';
    case UNKNOWN = '-';

    public static function fromRaw(?string $value): self
    {
        $val = strtoupper(trim($value ?? ''));
        if (str_contains($val, 'BERAT')) {
            return self::RUSAK_BERAT;
        }
        if (str_contains($val, 'RINGAN')) {
            return self::RUSAK_RINGAN;
        }
        if ($val === 'BAIK') {
            return self::BAIK;
        }
        return self::UNKNOWN;
    }

    public function label(): string
    {
        return match ($this) {
            self::BAIK => 'Baik',
            self::RUSAK_RINGAN => 'Rusak Ringan',
            self::RUSAK_BERAT => 'Rusak Berat',
            self::UNKNOWN => '-',
        };
    }

    public function warnaBadge(): string
    {
        return match ($this) {
            self::BAIK => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::RUSAK_RINGAN => 'bg-amber-100 text-amber-800 border-amber-300',
            self::RUSAK_BERAT => 'bg-rose-100 text-rose-800 border-rose-300',
            self::UNKNOWN => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}

