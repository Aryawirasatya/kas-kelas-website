<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('cash_periods')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->date('date');                                // tanggal pembayaran
            $table->unsignedBigInteger('amount');                // rupiah; boleh 1000, 2000, dll
            $table->foreignId('received_by')->constrained('users'); // bendahara
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['class_year_id', 'period_id', 'enrollment_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_payments');
    }
};
