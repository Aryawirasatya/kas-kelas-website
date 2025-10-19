<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('kas_nominal');           // rupiah, contoh 5000
            $table->enum('periode', ['mingguan'])->default('mingguan');
            $table->enum('pay_day_hint', ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_settings');
    }
};
