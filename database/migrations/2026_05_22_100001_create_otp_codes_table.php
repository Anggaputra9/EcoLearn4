<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel kode OTP untuk verifikasi pendaftaran (dan keperluan future-use).
 *
 * Tidak terikat ke users karena saat register user belum tentu ada.
 * Email dipakai sebagai key utama. Hash bcrypt dipakai agar OTP tidak tersimpan plain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('purpose', 30)->default('register'); // register | reset | dst
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
