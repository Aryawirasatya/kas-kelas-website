<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArrearAllocation extends Model
{
    protected $fillable = ['payment_id', 'period_id', 'allocated_amount'];

    public function payment()
    {
        return $this->belongsTo(CashPayment::class, 'payment_id');
    }

    public function period()
    {
        return $this->belongsTo(CashPeriod::class, 'period_id');
    }
}
