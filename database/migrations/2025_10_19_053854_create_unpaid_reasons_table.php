<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unpaid_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('cash_periods')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->text('reason')->nullable();                  // "sakit", "izin", dst
            $table->foreignId('set_by')->constrained('users');   // bendahara
            $table->timestamps();

            $table->unique(['period_id', 'enrollment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unpaid_reasons');
    }
};
