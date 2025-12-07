<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_expenses', function (Blueprint $table) {
            $table->id();

            // relasi ke tahun ajaran
            $table->foreignId('class_year_id')
                ->constrained('class_years')
                ->cascadeOnDelete();

            // relasi ke expense_requests (boleh nullable kalau nanti ada pengeluaran manual)
            $table->foreignId('request_id')
                ->nullable()
                ->constrained('expense_requests')
                ->nullOnDelete();

            // tanggal pengeluaran (bukan created_at)
            $table->date('date');

            // kategori pengeluaran (wajib)
            $table->foreignId('category_id')
                ->constrained('categories');

            // nominal
            $table->integer('amount');

            // guru yang menyetujui
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // kapan dicatat sebagai pengeluaran
            $table->timestamp('posted_at')->nullable();

            // keterangan tambahan
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_expenses');
    }
};
