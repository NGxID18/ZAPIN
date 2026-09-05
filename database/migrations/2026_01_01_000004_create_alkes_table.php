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
            $table->string('kode_inventaris')->unique();
            $table->string('nama_barang')->index();
            $table->foreignId('nomenklatur_id')->nullable()->constrained('nomenklatur')->onDelete('set null');
            $table->string('merk')->nullable()->index();
            $table->string('tipe')->nullable();
            $table->string('nomor_seri')->nullable()->index();
            $table->string('tahun_pengadaan')->nullable();
            $table->integer('jumlah')->default(1);
            $table->string('cara_perolehan')->nullable(); // DAK 2023, APBD 2021, HIBAH APBN, BLUD, Beli Sendiri
            $table->decimal('nilai_perolehan', 18, 2)->default(0);
            
            // Ruangan Penempatan Asli & Lokasi Fisik Keberadaan Alat saat ini
            $table->foreignId('ruangan_id')->nullable()->constrained('ruangan')->onDelete('cascade');
            $table->foreignId('lokasi_ruangan_id')->nullable()->constrained('ruangan')->onDelete('cascade');
            $table->string('lokasi_saat_ini_note')->nullable(); // Misal: "1 Berada di Poli", "1 di Perinatology"

            $table->string('status')->default('tersedia')->index(); // tersedia, sedang_digunakan, dalam_perbaikan
            $table->string('kondisi')->default('baik')->index(); // baik, rusak_ringan, rusak_berat
            $table->string('status_kalibrasi')->default('BELUM DIKALIBRASI')->index(); // SUDAH DIKALIBRASI, BELUM DIKALIBRASI
            $table->string('aspak_status')->default('TERDATA'); // TERDATA, TIDAK TERDATA
            $table->boolean('kib_status')->default(false); // true / false
            $table->date('tanggal_kalibrasi_terakhir')->nullable();
            $table->date('tanggal_kalibrasi_berikutnya')->nullable();
            $table->string('sertifikat_kalibrasi')->nullable();
            $table->json('sertifikat_kalibrasi_history')->nullable();
            $table->string('foto_alat')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Composite Indexes
            $table->index(['ruangan_id', 'status']);
            $table->index(['lokasi_ruangan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alkes');
    }
};
