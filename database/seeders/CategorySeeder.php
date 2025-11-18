<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create([
            'name' => 'Kas Mingguan',
            'type' => 'income',
            'description' => 'Pemasukan rutin mingguan dari siswa',
        ]);
    }
}
