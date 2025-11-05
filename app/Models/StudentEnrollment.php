<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'class_year_id','student_user_id','nis','is_active','is_treasurer'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_treasurer' => 'boolean',
    ];

    public function year()
    {
        return $this->belongsTo(ClassYear::class, 'class_year_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'student_user_id');
    }

    public function scopeActive($q){ return $q->where('is_active',1); }
}
