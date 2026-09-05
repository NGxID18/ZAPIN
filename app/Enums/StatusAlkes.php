<?php

namespace App\Enums;

enum StatusAlkes: string
{
    case TERSEDIA = 'tersedia';
    case SEDANG_DIGUNAKAN = 'sedang_digunakan';
    case DALAM_PERBAIKAN = 'dalam_perbaikan';
    case PROSES_KALIBRASI = 'proses_kalibrasi';
    case AFKIR = 'afkir';
    case DIPINJAM = 'dipinjam';

    public function label(): string
    {
        return match ($this) {
            self::TERSEDIA => 'Tersedia / Standby',
            self::SEDANG_DIGUNAKAN => 'Sedang Digunakan',
            self::DALAM_PERBAIKAN => 'Dalam Perbaikan (Rusak)',
            self::PROSES_KALIBRASI => 'Proses Kalibrasi',
            self::AFKIR => 'Afkir / Non-Aktif',
            self::DIPINJAM => 'Dipinjam',
        };
    }

    public function warnaBadge(): string
    {
        return match ($this) {
            self::TERSEDIA => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::SEDANG_DIGUNAKAN => 'bg-blue-100 text-blue-800 border-blue-300',
            self::DALAM_PERBAIKAN => 'bg-amber-100 text-amber-800 border-amber-300',
            self::PROSES_KALIBRASI => 'bg-purple-100 text-purple-800 border-purple-300',
            self::AFKIR => 'bg-rose-100 text-rose-800 border-rose-300',
            self::DIPINJAM => 'bg-sky-100 text-sky-800 border-sky-300',
        };
    }
}
