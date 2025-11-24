<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_years', function (Blueprint $table) {
            // Nama sekolah – cukup untuk tampilan/laporan, tidak untuk logika berat
            $table->string('school_name', 150)
                ->nullable()
                ->after('id'); // atau after('homeroom_user_id') sesuai selera
        });
    }

    public function down(): void
    {
        Schema::table('class_years', function (Blueprint $table) {
            $table->dropColumn('school_name');
        });
    }
};
