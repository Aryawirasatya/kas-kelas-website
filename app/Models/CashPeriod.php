<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_year_id',
        'year',
        'month',
        'week_no',
        'date_start',
        'date_end',
        'status',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end'   => 'date',
    ];

    public function classYear()
    {
        return $this->belongsTo(ClassYear::class);
    }

    // Scope praktis
    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }

    public function scopeClosed($q)
    {
        return $q->where('status', 'closed');
    }
}
