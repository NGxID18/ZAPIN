<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_role')->default('Petugas RS');
            $table->string('ruangan_name')->nullable();
            $table->string('action'); // Tambah Alkes, Edit Alkes, Pindah Ruangan Alkes, Lapor Perbaikan, Hapus Alkes
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
