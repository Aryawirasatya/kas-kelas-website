<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('student_enrollments') && 
            !Schema::hasColumn('student_enrollments', 'is_treasurer')) {

            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->boolean('is_treasurer')->default(false)->after('is_active');
                $table->index(['is_treasurer']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_enrollments') && 
            Schema::hasColumn('student_enrollments', 'is_treasurer')) {

            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropIndex(['is_treasurer']);
                $table->dropColumn('is_treasurer');
            });
        }
    }
};
