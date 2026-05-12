<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Buat tabel Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama_role'); // Administrator, Guru, Siswa
            $table->timestamps();
        });

        // 2. Tambahkan role_id ke tabel Users bawaan Laravel
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->default(3)->constrained('roles')->cascadeOnDelete();
        });

        // 3. Buat tabel Menus Dinamis
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_menu');
            $table->string('url');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('menus');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        Schema::dropIfExists('roles');
    }
};
