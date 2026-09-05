<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peminjaman_alkes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alkes_id')->constrained('alkes')->onDelete('cascade');
            $table->foreignId('ruangan_peminjam_id')->constrained('ruangan')->onDelete('cascade');
            $table->string('peminjam_nama');
            $table->dateTime('tanggal_pinjam');
            $table->dateTime('estimasi_kembali');
            $table->dateTime('tanggal_dikembalikan')->nullable();
            $table->string('status')->default('Dipinjam'); // Dipinjam, Dikembalikan
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_alkes');
    }
};
