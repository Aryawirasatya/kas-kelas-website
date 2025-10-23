<?php

// database/migrations/xxxx_xx_xx_xxxxxx_drop_role_enum_from_users.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration {
    public function up(): void
    {
        // 1) Backfill: enum -> Spatie (sekali jalan)
        User::whereNotNull('role')->chunkById(200, function ($users) {
            foreach ($users as $u) {
                // mapping enum → spatie role
                if (in_array($u->role, ['guru','bendahara','siswa'])) {
                    $u->syncRoles([$u->role]); // replace all existing roles with the enum value
                }
            }
        });

        // 2) Drop kolom enum
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['guru','bendahara','siswa'])->default('siswa')->after('gender');
        });
    }
};
