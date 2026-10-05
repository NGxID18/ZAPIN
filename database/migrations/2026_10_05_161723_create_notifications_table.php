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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('ews_kalibrasi')->index();
            $table->foreignId('alkes_id')->nullable()->constrained('alkes')->cascadeOnDelete();
            $table->string('target_role')->default('elektromedis')->index();
            $table->string('judul');
            $table->text('pesan');
            $table->string('stage')->nullable()->index(); // 'H-30', 'H-7', 'EXPIRED'
            $table->date('target_date')->nullable()->index(); // Tanggal kalibrasi berikutnya yang memicu notif
            $table->string('level')->default('warning'); // 'info', 'warning', 'danger'
            $table->string('url')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Memastikan 1 alkes hanya menerima 1 notifikasi per tahap (H-30 atau H-7) untuk 1 tanggal tenggat kalibrasi
            $table->unique(['alkes_id', 'stage', 'target_date'], 'unique_alkes_stage_target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
