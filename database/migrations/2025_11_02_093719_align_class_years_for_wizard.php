<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Tambah kolom homeroom_user_id (nullable) + FK ke users
        if (!Schema::hasColumn('class_years', 'homeroom_user_id')) {
            Schema::table('class_years', function (Blueprint $t) {
                $t->unsignedBigInteger('homeroom_user_id')->nullable()->after('homeroom_name');
            });

            Schema::table('class_years', function (Blueprint $t) {
                $t->foreign('homeroom_user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();
            });
        }

        // 2) Ubah enum status agar ada 'draft' dan default 'draft'
        // Catatan: pakai DB::statement karena MySQL ENUM perlu ALTER.
        DB::statement("ALTER TABLE class_years 
            MODIFY status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Balikkan enum seperti dump awal
        DB::statement("ALTER TABLE class_years 
            MODIFY status ENUM('active','archived') NOT NULL DEFAULT 'active'");

        // Lepas FK + drop kolom jika ada
        if (Schema::hasColumn('class_years', 'homeroom_user_id')) {
            Schema::table('class_years', function (Blueprint $t) {
                $t->dropForeign(['homeroom_user_id']);
                $t->dropColumn('homeroom_user_id');
            });
        }
    }
};
