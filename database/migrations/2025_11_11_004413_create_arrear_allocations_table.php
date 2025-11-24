<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arrear_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');   // fk ke cash_payments
            $table->unsignedBigInteger('period_id');    // fk ke cash_periods (periode yang dialokasikan)
            $table->unsignedInteger('allocated_amount'); // nominal yang dialokasikan (Rp)
            $table->timestamps();

            $table->index('payment_id');
            $table->index('period_id');

            $table->foreign('payment_id')->references('id')->on('cash_payments')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('cash_periods')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrear_allocations');
    }
};
