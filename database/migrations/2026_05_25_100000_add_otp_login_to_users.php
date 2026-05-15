<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah preferensi OTP login (2FA via email) ke tabel users.
 * Default mati. User bisa mengaktifkannya dari halaman profil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'otp_login_enabled')) {
                $table->boolean('otp_login_enabled')->default(false)->after('theme');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'otp_login_enabled')) {
                $table->dropColumn('otp_login_enabled');
            }
        });
    }
};
