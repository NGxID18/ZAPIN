<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'action',
        'description',
        'user_role',
        'ruangan_name',
        'ip_address',
    ];

    public static function record(string $action, string $description, ?string $ruanganName = null, ?string $userRole = null): self
    {
        return self::create([
            'action' => $action,
            'description' => $description,
            'user_role' => $userRole ?? session('user_role_label', 'Sistem ZAPIN'),
            'ruangan_name' => $ruanganName ?? session('user_ruangan_name', 'Pusat'),
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}

