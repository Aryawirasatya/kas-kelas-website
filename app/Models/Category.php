<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'type',   // 'expense' (fixed untuk pengeluaran)
    ];

    // nggak ada casts is_active / display_order lagi

    public function expenseRequests()
    {
        return $this->hasMany(ExpenseRequest::class);
    }
}
