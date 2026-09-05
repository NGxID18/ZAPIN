<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_pemeliharaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alkes_id')->constrained('alkes')->onDelete('cascade');
            $table->string('jenis_tindakan')->default('Perbaikan'); // Perbaikan, Rutin, Kalibrasi
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('pelaksana_vendor')->nullable();
            $table->text('deskripsi_kerusakan')->nullable();
            $table->text('tindakan_perbaikan')->nullable();
            $table->decimal('biaya', 15, 2)->default(0);
            $table->string('status_hasil')->default('Selesai'); // Selesai, Proses, Gagal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_pemeliharaan');
    }
};
