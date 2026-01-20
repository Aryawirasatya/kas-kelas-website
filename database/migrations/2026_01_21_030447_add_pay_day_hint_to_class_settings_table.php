<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_settings', function (Blueprint $table) {
            // tambah hanya kalau belum ada
            if (!Schema::hasColumn('class_settings', 'pay_day_hint')) {
                $table->string('pay_day_hint', 10)->nullable()->after('periode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_settings', function (Blueprint $table) {
            if (Schema::hasColumn('class_settings', 'pay_day_hint')) {
                $table->dropColumn('pay_day_hint');
            }
        });
    }
};
