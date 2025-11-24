<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_year_id',
        'category_id',
        'requested_by',
        'approved_by',
        'amount',
        'reason',
        'status',
        'approved_at',
    ];

    // 🔗 Relasi ke tahun ajaran
    public function classYear()
    {
        return $this->belongsTo(ClassYear::class);
    }

    // 🔗 Relasi ke kategori (ATK, acara, dll)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 🔗 Relasi ke user yang membuat request
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // 🔗 Relasi ke user yang menyetujui (guru)
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // 🔗 Relasi ke cash expense (realisasi pengeluaran)
    public function cashExpense()
    {
        return $this->hasOne(CashExpense::class, 'request_id');
    }
}
