<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alkes', function (Blueprint $table) {
            $table->id();
            $table->integer('no_urut')->nullable()->index();
            $table->string('kode_inventaris')->nullable()->unique();
            $table->string('nama_barang')->index();
            $table->string('merk')->nullable();
            $table->string('tipe')->nullable();
            $table->string('nomor_seri')->nullable()->index();
            $table->string('tahun')->nullable();
            $table->integer('jumlah')->default(1);
            $table->string('cara_perolehan')->nullable();
            $table->string('nilai_perolehan')->nullable();
            $table->string('distributor')->nullable();

            $table->foreignId('ruangan_id')->constrained('ruangan')->cascadeOnDelete();
            $table->foreignId('lokasi_ruangan_id')->nullable()->constrained('ruangan')->nullOnDelete();
            $table->string('lokasi_saat_ini_note')->nullable();

            // Kondisi persis dari spreadsheet (BAIK, RUSAK RINGAN, RUSAK BERAT, atau null)
            $table->string('kondisi')->nullable()->index();
            $table->string('status')->default('Tersedia')->index();

            // Kolom spesifik Kemenkes / Aset dari spreadsheet asli
            $table->string('aspak')->nullable();
            $table->string('kib')->nullable();
            $table->string('non_kib_dan_aspak')->nullable();
            $table->string('akl_akd')->nullable();

            // Kalibrasi & Dokumen
            $table->string('status_kalibrasi')->default('BELUM DIKALIBRASI')->index();
            $table->date('tanggal_kalibrasi_terakhir')->nullable();
            $table->date('tanggal_kalibrasi_berikutnya')->nullable();
            $table->string('sertifikat_kalibrasi')->nullable();
            $table->json('sertifikat_kalibrasi_history')->nullable();

            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alkes');
    }
};

