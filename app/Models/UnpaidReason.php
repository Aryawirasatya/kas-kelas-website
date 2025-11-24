<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnpaidReason extends Model
{
    protected $fillable = [
        'class_year_id',
        'period_id',
        'enrollment_id',
        'reason',
        'set_by',
    ];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function period()
    {
        return $this->belongsTo(CashPeriod::class, 'period_id');
    }

    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
