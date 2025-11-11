<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     * (Opsional kalau nama tabel sama dengan bentuk jamak dari model)
     */
    protected $table = 'categories';

    /**
     * Kolom yang boleh diisi (mass assignment).
     */
    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    /**
     * Jika ingin menambahkan helper label atau format tampilan.
     * (Opsional — bisa dipakai di view)
     */
    public function getTypeLabelAttribute()
    {
        return $this->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
    }
}
