<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('year');                        // kalender
            $table->tinyInteger('month');                        // 1..12
            $table->tinyInteger('week_no');                      // minggu ke- dalam bulan tsb
            $table->date('date_start');                          // biasanya Senin
            $table->date('date_end');                            // biasanya Jumat
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->unique(['class_year_id', 'year', 'month', 'week_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_periods');
    }
};
