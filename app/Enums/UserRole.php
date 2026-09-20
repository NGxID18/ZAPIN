<?php

namespace App\Enums;

enum UserRole: string
{
    case ELEKTROMEDIS = 'elektromedis';
    case RUANGAN = 'ruangan';
    case TATA_USAHA = 'tata_usaha';

    public function label(): string
    {
        return match ($this) {
            self::ELEKTROMEDIS => 'Instalasi Elektromedis',
            self::RUANGAN => 'Instalasi / Ruangan',
            self::TATA_USAHA => 'Manajemen / Penunjang',
        };
    }
}

