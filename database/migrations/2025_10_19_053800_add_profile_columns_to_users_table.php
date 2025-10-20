<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom opsional agar kompatibel dengan akun lama
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('nisn', 20)->nullable()->after('username');     // khusus siswa
            $table->enum('gender', ['L', 'P'])->nullable()->after('nisn');
            $table->enum('role', ['guru', 'bendahara', 'siswa'])->default('siswa')->after('gender');
            $table->boolean('active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'nisn', 'gender', 'role', 'active']);
        });
    }
};
