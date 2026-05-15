<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Kode OTP untuk verifikasi email saat pendaftaran.
 *
 * Cara pakai singkat:
 *   $otp = OtpCode::issue($email, 'register');   // hasilkan kode 6 digit + simpan hash
 *   $code = $otp->plain;                          // kode plaintext untuk dikirim email
 *   OtpCode::verify($email, '123456', 'register') // -> true | false (sekaligus tandai verified)
 */
class OtpCode extends Model
{
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
        // Hapus OTP lama yang belum diverifikasi untuk email+purpose ini
        static::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
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

    public static function verify(string $email, string $code, string $purpose = 'register'): bool
    {
        $otp = static::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) return false;
        if ($otp->expires_at->isPast()) return false;
        if ($otp->attempts >= 5) return false;

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) return false;

        $otp->update(['verified_at' => now()]);
        return true;
    }

    public static function markPurposeVerified(string $email, string $purpose = 'register'): bool
    {
        return (bool) static::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(30))
            ->exists();
    }

    public function maskedEmail(): string
    {
        return Str::mask($this->email, '*', 2, max(1, strpos($this->email, '@') - 4));
    }
}
