<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── 1. Create all Permissions (sesuai PRD Matriks Hak Akses) ──
        $permissions = [
            // Manajemen Akun Client
            'manage clients',       // Super Admin: Akses Penuh

            // Gedung & Ruangan
            'view buildings',       // Super Admin, Maintenance, Operator: Akses Baca
            'manage buildings',     // Client: Akses Penuh (CRUD gedung & ruangan)

            // Manajemen Staf
            'manage staff',         // Client: Akses Penuh (CRUD akun staf)

            // Registrasi Alat (ESP32)
            'register nodes',       // Maintenance: Akses Penuh
            'view nodes',           // Operator: Akses Baca

            // Konfigurasi I/O Mapping
            'configure io-mapping', // Maintenance: Akses Penuh

            // Live Monitoring
            'view monitoring',      // Client, Maintenance, Operator, Viewer

            // Kendali Perangkat
            'control devices',      // Operator: Eksekusi (kirim command MQTT)
            'diagnostic devices',   // Maintenance: Diagnostik (baca status perangkat)

            // Approval System (Tiket)
            'request assistance',   // Viewer: Kirim Permintaan Bantuan
            'approve requests',     // Operator: Approve/Reject tiket

            // Dashboard
            'view dashboard',       // All roles
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ── 2. Create Roles & assign permissions ───────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions($permissions); // All access

        $client = Role::firstOrCreate(['name' => 'client']);
        $client->syncPermissions([
            'manage buildings',
            'manage staff',
            'view monitoring',
            'view dashboard',
        ]);

        $maintenance = Role::firstOrCreate(['name' => 'maintenance']);
        $maintenance->syncPermissions([
            'view buildings',
            'register nodes',
            'configure io-mapping',
            'view monitoring',
            'diagnostic devices',
            'view dashboard',
        ]);

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'view buildings',
            'view nodes',
            'view monitoring',
            'control devices',
            'approve requests',
            'view dashboard',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'view monitoring',
            'request assistance',
            'view dashboard',
        ]);

        // ── 3. Create Super Admin account ──────────────────────
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@bems.id'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('Ddw9889##'),
                'role'     => 'super_admin',
            ]
        );
        $adminUser->assignRole('super_admin');
    }
}
