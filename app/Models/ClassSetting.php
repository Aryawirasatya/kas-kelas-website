<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSetting extends Model
{
    protected $fillable = ['class_year_id','kas_nominal','periode' ];

    public function year()
    {
        return $this->belongsTo(ClassYear::class, 'class_year_id');
    }
}
