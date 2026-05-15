<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Kode OTP untuk verifikasi email (registrasi, login 2FA, reset, dll).
 *
 * Cara pakai singkat:
 *   $otp = OtpCode::issue($email, 'register');     // hasilkan kode 6 digit + simpan hash
 *   $code = $otp->plain;                            // kode plaintext untuk dikirim email
 *   OtpCode::verify($email, '123456', 'register')   // -> true | false (sekaligus tandai verified)
 */
class OtpCode extends Model
{
    /** Maksimum percobaan salah sebelum kode dikunci. */
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'email', 'purpose', 'code_hash', 'attempts', 'expires_at', 'verified_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'attempts'    => 'integer',
    ];

    /** Plain code yang baru di-generate (tidak disimpan ke DB; hanya di memori model). */
    public ?string $plain = null;

    public static function issue(string $email, string $purpose = 'register', int $ttlMinutes = 10): self
    {
        // Hapus OTP lama (verified atau belum) untuk email+purpose ini agar tidak menumpuk.
        // Tujuannya: hanya satu kode aktif sekaligus, dan reset counter attempts.
        static::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->delete();

        $code = (string) random_int(100000, 999999);
        $otp  = static::create([
            'email'      => $email,
            'purpose'    => $purpose,
            'code_hash'  => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
        $otp->plain = $code;
        return $otp;
    }

    /**
     * Verifikasi kode. Hanya kode yang SALAH yang menambah counter attempts,
     * sehingga kode benar tidak ikut menghabiskan kuota percobaan.
     */
    public static function verify(string $email, string $code, string $purpose = 'register'): bool
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) return false;

        $otp = static::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) return false;
        if ($otp->expires_at->isPast()) return false;
        if ($otp->attempts >= self::MAX_ATTEMPTS) return false;

        if (! Hash::check($code, $otp->code_hash)) {
            // Hanya gagal yang menambah attempts.
            $otp->increment('attempts');
            return false;
        }

        $otp->forceFill(['verified_at' => now()])->save();
        return true;
    }

    /**
     * Cek apakah purpose sudah pernah diverifikasi dalam window waktu tertentu.
     */
    public static function markPurposeVerified(string $email, string $purpose = 'register', int $withinMinutes = 30): bool
    {
        return (bool) static::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes($withinMinutes))
            ->exists();
    }

    public function maskedEmail(): string
    {
        return Str::mask($this->email, '*', 2, max(1, strpos($this->email, '@') - 4));
    }
}
