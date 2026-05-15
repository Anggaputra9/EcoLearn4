<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        DB::table('roles')->insert([
            ['id' => 1, 'nama_role' => 'Administrator'],
            ['id' => 2, 'nama_role' => 'Guru'],
            ['id' => 3, 'nama_role' => 'Siswa'],
        ]);

        // Akun default (bcrypt via Hash::make)
        DB::table('users')->insert([
            [
                'name'     => 'Super Admin',
                'email'    => 'admin@ekoscribe.id',
                'password' => Hash::make('password123'),
                'role_id'  => 1,
            ],
            [
                'name'     => 'Guru Demo',
                'email'    => 'guru@ekoscribe.id',
                'password' => Hash::make('password123'),
                'role_id'  => 2,
            ],
            [
                'name'     => 'Siswa Demo',
                'email'    => 'siswa@ekoscribe.id',
                'password' => Hash::make('password123'),
                'role_id'  => 3,
            ],
        ]);

        // Menu navigasi per role (sinkron dengan route eksplisit)
        DB::table('menus')->insert([
            // Administrator
            ['nama_menu' => 'Dashboard',        'url' => '/dashboard',     'role_id' => 1],
            ['nama_menu' => 'Kelola Pengguna',  'url' => '/admin/users',   'role_id' => 1],
            ['nama_menu' => 'Kelola Menu',      'url' => '/admin/menus',   'role_id' => 1],
            ['nama_menu' => 'Konfigurasi AI',   'url' => '/admin/settings','role_id' => 1],
            ['nama_menu' => 'API Key Pool',     'url' => '/admin/ai-keys', 'role_id' => 1],
            ['nama_menu' => 'Email & Notif',    'url' => '/admin/mail',    'role_id' => 1],
            ['nama_menu' => 'Changelog',        'url' => '/admin/changelogs','role_id' => 1],

            // Guru
            ['nama_menu' => 'Dashboard',        'url' => '/dashboard',         'role_id' => 2],
            ['nama_menu' => 'Materi Saya',      'url' => '/teacher',           'role_id' => 2],
            ['nama_menu' => 'Kelas Saya',       'url' => '/teacher/classrooms','role_id' => 2],

            // Siswa
            ['nama_menu' => 'Dashboard',        'url' => '/dashboard',         'role_id' => 3],
            ['nama_menu' => 'Daftar Materi',    'url' => '/student',           'role_id' => 3],
            ['nama_menu' => 'Kelas Saya',       'url' => '/student/classrooms','role_id' => 3],
        ]);
    }
}
