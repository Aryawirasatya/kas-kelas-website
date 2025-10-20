<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_year_id')->nullable()->constrained()->cascadeOnDelete(); // null = global
            $table->string('name');
            $table->enum('type', ['income', 'expense']);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['class_year_id', 'name', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
