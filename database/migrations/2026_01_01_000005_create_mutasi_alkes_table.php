<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_alkes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alkes_id')->constrained('alkes')->onDelete('cascade');
            $table->foreignId('ruangan_asal_id')->nullable()->constrained('ruangan')->onDelete('set null');
            $table->foreignId('ruangan_tujuan_id')->nullable()->constrained('ruangan')->onDelete('set null');
            $table->timestamp('tanggal_mutasi')->useCurrent();
            $table->string('pemohon')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->text('alasan_mutasi')->nullable();
            $table->string('status_persetujuan')->default('Disetujui');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_alkes');
    }
};
