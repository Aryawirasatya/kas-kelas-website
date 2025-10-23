<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Guru / Wali Kelas
        $guru = User::updateOrCreate(
            ['email' => 'guru@finote.test'],
            [
                'name' => 'Guru Wali Kelas',
                'password' => Hash::make('password123'),
                'role' => 'guru',
                'gender' => 'L',
                'active' => true,
            ]
        );
        $guru->syncRoles(['guru']);

        // 2️⃣ Buat daftar siswa (otomatis)
        $students = collect([
            ['name' => 'Siswa 01', 'email' => 'siswa01@finote.test', 'gender' => 'L'],
            ['name' => 'Siswa 02', 'email' => 'siswa02@finote.test', 'gender' => 'P'],
            ['name' => 'Siswa 03', 'email' => 'siswa03@finote.test', 'gender' => 'L'],
            ['name' => 'Siswa 04', 'email' => 'siswa04@finote.test', 'gender' => 'P'],
            ['name' => 'Siswa 05', 'email' => 'siswa05@finote.test', 'gender' => 'L'],
            ['name' => 'Siswa 06', 'email' => 'siswa06@finote.test', 'gender' => 'P'],
            ['name' => 'Siswa 07', 'email' => 'siswa07@finote.test', 'gender' => 'L'],
            ['name' => 'Siswa 08', 'email' => 'siswa08@finote.test', 'gender' => 'L'],
            ['name' => 'Siswa 09', 'email' => 'siswa09@finote.test', 'gender' => 'P'],
            ['name' => 'Siswa 10', 'email' => 'siswa10@finote.test', 'gender' => 'L'],
        ]);

        $students->each(function ($s) {
            $user = User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'siswa',
                    'gender' => $s['gender'],
                    'active' => true,
                ]
            );
            $user->syncRoles(['siswa']);
        });

        // 3️⃣ Pilih otomatis 2 siswa jadi bendahara (dari daftar yang sudah ada)
        $bendaharaList = User::where('role', 'siswa')->inRandomOrder()->take(2)->get();

        foreach ($bendaharaList as $bendahara) {
            $bendahara->update(['role' => 'bendahara']); // update kolom enum role
            $bendahara->syncRoles(['bendahara']); // sync Spatie role
        }

        // 4️⃣ Info log di console
        $this->command->info("✅ Seeder berhasil dijalankan:");
        $this->command->info("- 1 Guru (email: guru@finote.test / password123)");
        $this->command->info("- 10 Siswa, termasuk 2 yang diangkat jadi Bendahara.");
    }
}
