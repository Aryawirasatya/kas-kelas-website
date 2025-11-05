<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Guru (wali kelas)
        $guru = User::updateOrCreate(
            ['email' => 'guru@example.com'],
            [
                'name' => 'Guru Wali Kelas',
                'password' => Hash::make('password'), // ganti di env produksi
                'active' => true,
            ]
        );
        $guru->syncRoles(['guru']);

        // Bendahara contoh
        $bendahara = User::updateOrCreate(
            ['email' => 'bendahara@example.com'],
            [
                'name' => 'Bendahara 1',
                'password' => Hash::make('password'),
                'active' => true,
            ]
        );
        $bendahara->syncRoles(['bendahara']);

        // Siswa contoh
        $siswa = User::updateOrCreate(
            ['email' => 'siswa@example.com'],
            [
                'name' => 'Siswa 1',
                'password' => Hash::make('password'),
                'active' => true,
            ]
        );
        $siswa->syncRoles(['siswa']);
    }
}
