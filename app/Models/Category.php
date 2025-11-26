<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'class_year_id',
        'name',
        'description',
        'type',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function classYear()
    {
        return $this->belongsTo(ClassYear::class);
    }

    public function expenseRequests()
    {
        return $this->hasMany(ExpenseRequest::class);
    }
}
