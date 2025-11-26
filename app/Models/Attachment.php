<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'attachments';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_path',
        'mime',
        'size',
    ];

    /**
     * Relasi polymorphic: setiap attachment menempel ke model lain.
     * Misal: ExpenseRequest, CashExpense, dll.
     */
    public function attachable()
    {
        return $this->morphTo();
    }
}
