<?php

namespace App\Enums;

enum KondisiAlkes: string
{
    case BAIK = 'baik';
    case RUSAK_RINGAN = 'rusak_ringan';
    case RUSAK_BERAT = 'rusak_berat';

    public function label(): string
    {
        return match ($this) {
            self::BAIK => 'Baik (Normal)',
            self::RUSAK_RINGAN => 'Rusak Ringan',
            self::RUSAK_BERAT => 'Rusak Berat',
        };
    }

    public function warnaBadge(): string
    {
        return match ($this) {
            self::BAIK => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::RUSAK_RINGAN => 'bg-yellow-50 text-yellow-700 border-yellow-200',
            self::RUSAK_BERAT => 'bg-red-50 text-red-700 border-red-200',
        };
    }
}
