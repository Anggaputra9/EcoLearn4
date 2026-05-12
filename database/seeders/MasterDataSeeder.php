<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder {
    public function run(): void {
        // 1. Suntik Data Roles
        DB::table('roles')->insert([
            ['id' => 1, 'nama_role' => 'Administrator'],
            ['id' => 2, 'nama_role' => 'Guru'],
            ['id' => 3, 'nama_role' => 'Siswa'],
        ]);

        // 2. Suntik Data Akun Super Admin
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@ecolearn4.com',
            'password' => Hash::make('password123'), // Password default
            'role_id' => 1,
        ]);
        DB::table('users')->insert([
            'name' => 'Guru',
            'email' => 'guru@ecolearn4.com',
            'password' => Hash::make('password123'), // Password default
            'role_id' => 2,
        ]);
        DB::table('users')->insert([
            'name' => 'Siswa',
            'email' => 'siswa@ecolearn4.com',
            'password' => Hash::make('password123'), // Password default
            'role_id' => 3,
        ]);

        // 3. Suntik Data Menu Berdasarkan Role
        DB::table('menus')->insert([
            // Menu Administrator
            ['nama_menu' => 'Dashboard Admin', 'url' => '/dashboard', 'role_id' => 1],
            ['nama_menu' => 'Kelola Pengguna', 'url' => '/admin/users', 'role_id' => 1],
            ['nama_menu' => 'Kelola Menu', 'url' => '/admin/menus', 'role_id' => 1],
            // Menu Guru
            ['nama_menu' => 'Dashboard Guru', 'url' => '/dashboard', 'role_id' => 2],
            ['nama_menu' => 'Buat Soal AI', 'url' => '/guru/generate', 'role_id' => 2],
            ['nama_menu' => 'Bank Soal', 'url' => '/guru/soal', 'role_id' => 2],
            // Menu Siswa
            ['nama_menu' => 'Dashboard Siswa', 'url' => '/dashboard', 'role_id' => 3],
            ['nama_menu' => 'Mulai Ujian', 'url' => '/siswa/ujian', 'role_id' => 3],
        ]);
    }
}
