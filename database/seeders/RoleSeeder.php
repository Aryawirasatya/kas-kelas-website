<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan cache permission bersih
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ====== Definisikan permissions inti Finote ======
        $permissions = [
            // Guru / Wali Kelas (kontrol & monitoring)
            'tahun-ajaran.create', 'tahun-ajaran.close', 'kelas.manage',
            'nominal.manage', 'bendahara.assign', 'laporan.view.all', 'pengeluaran.approve',

            // Bendahara (operasional harian)
            'kasmasuk.create', 'kasmasuk.view', 'kasmasuk.export',
            'pengeluaran.request', 'pengeluaran.view',
            'unpaid_reason.fill', 'dashboard.view.ops',

            // Siswa (transparansi)
            'riwayat.view.self', 'dashboard.view.self',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // ====== Buat roles ======
        $guru      = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        $bendahara = Role::firstOrCreate(['name' => 'bendahara', 'guard_name' => 'web']);
        $siswa     = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        // ====== Berikan permissions ke setiap role ======

        // Guru / Wali Kelas: pantau & atur
        $guru->syncPermissions([
            'tahun-ajaran.create', 'tahun-ajaran.close', 'kelas.manage',
            'nominal.manage', 'bendahara.assign', 'laporan.view.all',
            'pengeluaran.approve', 'pengeluaran.view', 'dashboard.view.ops'
        ]);

        // Bendahara: operasional penuh
        $bendahara->syncPermissions([
            'kasmasuk.create', 'kasmasuk.view', 'kasmasuk.export',
            'pengeluaran.request', 'pengeluaran.view',
            'unpaid_reason.fill', 'laporan.view.all', 'dashboard.view.ops'
        ]);

        // Siswa: lihat/transparansi
        $siswa->syncPermissions([
            'riwayat.view.self', 'dashboard.view.self'
        ]);
    }
}
