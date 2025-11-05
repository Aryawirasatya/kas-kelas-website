<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashPeriod extends Model
{
    protected $fillable = ['class_year_id','year','month','week_no','date_start','date_end','status'];
    public function classYear(){ return $this->belongsTo(ClassYear::class,'class_year_id'); }
    public function scopeOpen($q){ return $q->where('status','open'); }
}
