<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('class_years', function (Blueprint $table) {
            $table->string('level', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('class_years', function (Blueprint $table) {
            $table->string('level', 10)->change();
        });
    }
};
