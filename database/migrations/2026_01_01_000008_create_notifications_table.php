<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alkes_id')->nullable()->constrained('alkes')->onDelete('cascade');
            $table->foreignId('ruangan_asal_id')->nullable()->constrained('ruangan')->onDelete('set null');
            $table->string('judul');
            $table->text('pesan');
            $table->string('tipe')->default('laporan_kerusakan'); // laporan_kerusakan, perbaikan_selesai
            $table->boolean('dibaca')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
