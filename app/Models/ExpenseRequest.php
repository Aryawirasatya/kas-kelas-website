<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseRequest extends Model
{
    use SoftDeletes;

    protected $table = 'expense_requests';

    protected $fillable = [
        'class_year_id',
        'request_date',
        'category_id',
        'amount',
        'description',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'reject_reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at'  => 'datetime',
    ];

    // === Relasi ===

    public function classYear()
    {
        return $this->belongsTo(ClassYear::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // === Scope bantu ===

    public function scopeForClassYear($query, $classYearId)
    {
        return $query->where('class_year_id', $classYearId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
