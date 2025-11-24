<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassYear extends Model
{
    protected $fillable = [
        'school_name',   
        'class_label',
        'level',
        'academic_year',
        'homeroom_name',
        'homeroom_user_id',
        'status',
    ];

    // Contoh scope active (sepertinya sudah ada di project kamu)
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function setting()
    {
        return $this->hasOne(ClassSetting::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function periods()
    {
        return $this->hasMany(CashPeriod::class);
    }
}
