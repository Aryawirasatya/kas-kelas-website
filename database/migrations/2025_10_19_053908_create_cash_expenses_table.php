<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('expense_requests')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('category_id')->constrained('categories');
            $table->unsignedBigInteger('amount');
            $table->foreignId('approved_by')->constrained('users'); // guru yang ACC
            $table->timestamp('posted_at')->nullable();             // waktu posting
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['class_year_id', 'date', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_expenses');
    }
};
