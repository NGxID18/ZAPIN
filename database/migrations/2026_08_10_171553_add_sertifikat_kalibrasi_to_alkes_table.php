<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('alkes', 'sertifikat_kalibrasi')) {
            Schema::table('alkes', function (Blueprint $table) {
                $table->string('sertifikat_kalibrasi')->nullable()->after('tanggal_kalibrasi_berikutnya');
            });
        }
    }

    public function down(): void
    {
        Schema::table('alkes', function (Blueprint $table) {
            $table->dropColumn('sertifikat_kalibrasi');
        });
    }
};
