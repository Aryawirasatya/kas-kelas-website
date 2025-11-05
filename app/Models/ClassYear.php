<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassYear extends Model
{
    protected $fillable = [
        'class_label','level','academic_year',
        'homeroom_name','homeroom_user_id','status'
    ];

    public function setting()
    {
        return $this->hasOne(ClassSetting::class, 'class_year_id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'class_year_id');
    }

    public function periods()
    {
        return $this->hasMany(CashPeriod::class, 'class_year_id');
    }

    public function scopeActive($q){ return $q->where('status','active'); }
}
