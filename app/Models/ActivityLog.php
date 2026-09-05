<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_role',
        'ruangan_name',
        'action',
        'description',
        'ip_address',
    ];

    public static function record(string $action, string $description, ?string $ruanganName = null): self
    {
        return self::create([
            'user_role' => session('user_role', 'Petugas RS'),
            'ruangan_name' => $ruanganName ?? session('user_ruangan_name', 'RS Central'),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
