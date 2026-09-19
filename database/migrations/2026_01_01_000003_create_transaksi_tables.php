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
            $table->foreignId('alkes_id')->constrained('alkes')->cascadeOnDelete();
            $table->foreignId('ruangan_asal_id')->constrained('ruangan')->cascadeOnDelete();
            $table->foreignId('ruangan_tujuan_id')->constrained('ruangan')->cascadeOnDelete();
            $table->string('pemohon');
            $table->string('penanggung_jawab');
            $table->text('alasan_mutasi');
            $table->timestamp('tanggal_mutasi')->useCurrent();
            $table->string('status')->default('Selesai');
            $table->timestamps();
        });

        Schema::create('peminjaman_alkes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alkes_id')->constrained('alkes')->cascadeOnDelete();
            $table->foreignId('ruangan_peminjam_id')->constrained('ruangan')->cascadeOnDelete();
            $table->string('peminjam_nama');
            $table->timestamp('tanggal_pinjam')->useCurrent();
            $table->timestamp('estimasi_kembali')->nullable();
            $table->timestamp('tanggal_dikembalikan')->nullable();
            $table->string('status')->default('Dipinjam')->index();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('log_pemeliharaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alkes_id')->constrained('alkes')->cascadeOnDelete();
            $table->string('jenis_tindakan')->default('Perbaikan');
            $table->timestamp('tanggal_mulai')->useCurrent();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->string('pelaksana_vendor')->nullable();
            $table->text('deskripsi_kerusakan')->nullable();
            $table->text('tindakan_perbaikan')->nullable();
            $table->decimal('biaya', 14, 2)->default(0);
            $table->string('status_hasil')->default('Proses')->index();
            $table->string('foto_kerusakan')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action')->index();
            $table->text('description');
            $table->string('user_role')->nullable();
            $table->string('ruangan_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('log_pemeliharaan');
        Schema::dropIfExists('peminjaman_alkes');
        Schema::dropIfExists('mutasi_alkes');
    }
};

