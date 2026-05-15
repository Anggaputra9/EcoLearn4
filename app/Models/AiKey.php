<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKey extends Model
{
    protected $table = 'ai_keys';

    protected $fillable = [
        'label', 'provider', 'model', 'api_key', 'priority',
        'is_active', 'quota_limit', 'quota_used', 'quota_reset_at',
        'quota_reset_period', 'last_used_at', 'last_error',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'priority'       => 'integer',
        'quota_limit'    => 'integer',
        'quota_used'     => 'integer',
        'quota_reset_at' => 'datetime',
        'last_used_at'   => 'datetime',
    ];

    /** Sembunyikan API key dari serialization default */
    protected $hidden = ['api_key'];

    public function maskedKey(): string
    {
        $key = (string) $this->api_key;
        $len = strlen($key);
        if ($len <= 8) return str_repeat('•', max(4, $len));
        return substr($key, 0, 4).str_repeat('•', max(6, $len - 8)).substr($key, -4);
    }

    public function quotaRemaining(): ?int
    {
        if ($this->quota_limit === null) return null;
        return max(0, (int) $this->quota_limit - (int) $this->quota_used);
    }

    public function quotaPercentUsed(): int
    {
        if (! $this->quota_limit) return 0;
        return (int) min(100, round(($this->quota_used / $this->quota_limit) * 100));
    }

    public function isExhausted(): bool
    {
        if ($this->quota_limit === null) return false;
        return $this->quota_used >= $this->quota_limit;
    }

    /** Reset kuota jika sudah lewat periode reset */
    public function maybeResetQuota(): void
    {
        if (! $this->quota_reset_at || $this->quota_reset_period === 'none') return;
        if (now()->lessThan($this->quota_reset_at)) return;

        $next = match ($this->quota_reset_period) {
            'daily'   => now()->addDay()->startOfDay(),
            'monthly' => now()->addMonth()->startOfMonth(),
            default   => null,
        };
        $this->update(['quota_used' => 0, 'quota_reset_at' => $next]);
    }

    /**
     * Limit kuota default berbasis tier free public masing-masing provider.
     * Dipakai saat admin tidak mengisi kolom limit secara manual.
     *
     *   - gemini      : Free tier umumnya ~1.500 request/hari (1.5/2.0 Flash)
     *   - openai      : Tergantung kredit; default 10.000 / bulan agar konservatif
     *   - anthropic   : Default 5.000 / bulan
     *   - openrouter  : 200 / hari untuk model `:free`, default ke 6.000 / bulan
     *   - groq        : 14.400 / hari free tier; ambil floor 10.000 / hari
     */
    public static function defaultQuotaFor(string $provider): array
    {
        // [limit, period]
        return match ($provider) {
            'gemini'     => [1500,  'daily'],
            'openai'     => [10000, 'monthly'],
            'anthropic'  => [5000,  'monthly'],
            'openrouter' => [6000,  'monthly'],
            'groq'       => [10000, 'daily'],
            default      => [1000,  'monthly'],
        };
    }
}


