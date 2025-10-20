<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_years', function (Blueprint $table) {
            $table->id();
            $table->string('class_label');                       // contoh: RPL 2
            $table->enum('level', ['X', 'XI', 'XII']);           // tingkat dipilih, tdk digabung nama
            $table->string('academic_year', 9);                  // "2025/2026"
            $table->string('homeroom_name')->nullable();         // wali kelas
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_years');
    }
};
