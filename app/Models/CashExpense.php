<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashExpense extends Model
{
    protected $table = 'cash_expenses';

    protected $fillable = [
        'class_year_id',
        'request_id',
        'date',
        'category_id',
        'amount',
        'approved_by',
        'posted_at',
        'description',
    ];
}
