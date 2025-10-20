<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->constrained()->cascadeOnDelete();
            $table->date('request_date');
            $table->foreignId('category_id')->constrained('categories');
            $table->unsignedBigInteger('amount');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users');       // bendahara
            $table->foreignId('approved_by')->nullable()->constrained('users'); // guru
            $table->timestamp('approved_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['class_year_id', 'status', 'request_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_requests');
    }
};
