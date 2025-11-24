<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashPayment extends Model
{
    protected $fillable = [
        'class_year_id',
        'period_id',
        'enrollment_id',
        'date',
        'amount',
        'received_by',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function period()
    {
        return $this->belongsTo(CashPeriod::class, 'period_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function classYear()
    {
        return $this->belongsTo(ClassYear::class, 'class_year_id');
    }

    public function allocations()
{
    return $this->hasMany(\App\Models\ArrearAllocation::class, 'payment_id');
}

}
