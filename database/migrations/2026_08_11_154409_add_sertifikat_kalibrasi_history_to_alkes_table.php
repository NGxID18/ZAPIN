<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('alkes', 'sertifikat_kalibrasi_history')) {
            Schema::table('alkes', function (Blueprint $table) {
                $table->text('sertifikat_kalibrasi_history')->nullable()->after('sertifikat_kalibrasi');
            });
        }
    }

    public function down(): void
    {
        Schema::table('alkes', function (Blueprint $table) {
            $table->dropColumn('sertifikat_kalibrasi_history');
        });
    }
};
