<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    // ... (bagian homeroom_user_id & ENUM status biarkan seperti yang sudah OK)

    // ===== [DE-DUP] academic_year =====
    // Hapus baris duplikat academic_year, simpan yang id paling kecil (atau yang paling awal)
    // NOTE: Aman kalau belum ada FK yang refer ke row duplikat.
    DB::statement("
        DELETE t1 FROM class_years t1
        INNER JOIN class_years t2
            ON t1.academic_year = t2.academic_year
           AND t1.id > t2.id
    ");

    // 3) Unique academic_year (cek dulu)
    $hasUnique = collect(DB::select("
        SHOW INDEX FROM class_years WHERE Key_name = 'class_years_academic_year_unique'
    "))->isNotEmpty();

    if (!$hasUnique) {
        Schema::table('class_years', function (Blueprint $t) {
            $t->unique('academic_year');
        });
    }

    // 4) Index status (cek dulu)
    $hasStatusIndex = collect(DB::select("
        SHOW INDEX FROM class_years WHERE Column_name = 'status'
    "))->isNotEmpty();

    if (!$hasStatusIndex) {
        Schema::table('class_years', function (Blueprint $t) {
            $t->index('status');
        });
    }
}


    public function down(): void
    {
        // Revert secukupnya (hati-hati jika sudah ada data 'draft')
        if (Schema::hasColumn('class_years', 'status')) {
            DB::table('class_years')->where('status', 'draft')->update(['status' => 'active']);
            DB::statement("
                ALTER TABLE class_years
                MODIFY status ENUM('active','archived')
                NOT NULL DEFAULT 'active'
            ");
        }

        try {
            Schema::table('class_years', fn (Blueprint $t) => $t->dropIndex(['status']));
        } catch (\Throwable $e) {}

        try {
            Schema::table('class_years', fn (Blueprint $t) => $t->dropUnique('class_years_academic_year_unique'));
        } catch (\Throwable $e) {}

        if (Schema::hasColumn('class_years', 'homeroom_user_id')) {
            Schema::table('class_years', function (Blueprint $t) {
                try { $t->dropForeign(['homeroom_user_id']); } catch (\Throwable $e) {}
                $t->dropColumn('homeroom_user_id');
            });
        }
    }
};
