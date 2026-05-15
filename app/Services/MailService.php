<?php

namespace App\Services;

use App\Models\MailKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * MailService — pengirim email multi-provider dengan rotasi multi-key.
 *
 * Provider: smtp | brevo | mailersend | sendpulse
 * Konfigurasi key disimpan di tabel `mail_keys`. Bila kosong, fallback ke setting tunggal lama
 * (mail.brevo.api_key, mail.mailersend.api_key, mail.sendpulse.client_id/secret).
 */
class MailService
{
    protected ?MailKey $usedKey = null;

    public function provider(): string
    {
        return (string) (Setting::get('mail.provider') ?? 'smtp');
    }

    public function fromEmail(): string
    {
        return (string) (Setting::get('mail.from_email') ?? config('mail.from.address') ?? 'no-reply@example.com');
    }

    public function fromName(): string
    {
        return (string) (Setting::get('mail.from_name') ?? config('mail.from.name') ?? config('app.name'));
    }

    public function lastUsedKey(): ?MailKey { return $this->usedKey; }

    /** Daftar key aktif untuk provider, urut prioritas (tanpa yang exhausted). */
    public function activeKeysFor(string $provider)
    {
        return MailKey::where('provider', $provider)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(function (MailKey $k) {
                $k->maybeResetQuota();
                return ! $k->isExhausted();
            })
            ->values();
    }

    public function providers(): array
    {
        return [
            'smtp'       => 'SMTP / PHPMailer (Laravel Mail)',
            'brevo'      => 'Brevo (Sendinblue) API',
            'mailersend' => 'MailerSend API',
            'sendpulse'  => 'SendPulse API',
        ];
    }

    /**
     * Kirim email berbasis HTML.
     * @param string|array $to email atau ['email' => 'name']
     */
    public function sendHtml(string|array $to, string $subject, string $html, ?string $text = null): bool
    {
        $recipients = is_array($to) ? $to : [$to => null];
        $provider = $this->provider();

        if ($provider === 'smtp') {
            try { return $this->sendViaSmtp($recipients, $subject, $html, $text); }
            catch (\Throwable $e) {
                Log::error('Mail SMTP gagal: '.$e->getMessage());
                return false;
            }
        }

        // Provider API-based: rotasi key
        $keys = $this->activeKeysFor($provider);
        if ($keys->isEmpty()) {
            // Fallback ke setting tunggal lama untuk backward-compat
            $keys = collect([$this->legacyKeyFor($provider)])->filter();
        }

        if ($keys->isEmpty()) {
            Log::error("Mail [$provider] tidak punya key aktif.");
            return false;
        }

        $errors = [];
        foreach ($keys as $key) {
            try {
                $this->dispatchProvider($provider, $key, $recipients, $subject, $html, $text);
                $this->markSuccess($key);
                $this->usedKey = $key;
                return true;
            } catch (\Throwable $e) {
                $errors[] = "[{$key->label}] ".$e->getMessage();
                $this->markFailure($key, $e->getMessage());
                Log::warning("Mail key gagal ({$key->label}): ".$e->getMessage());
            }
        }

        Log::error("Semua mail key gagal: ".implode(' | ', $errors));
        return false;
    }

    /* ============================================================ */

    protected function legacyKeyFor(string $provider): ?MailKey
    {
        switch ($provider) {
            case 'brevo':
                $k = (string) Setting::get('mail.brevo.api_key');
                return $k ? new MailKey(['label' => 'Legacy', 'provider' => 'brevo', 'api_key' => $k]) : null;
            case 'mailersend':
                $k = (string) Setting::get('mail.mailersend.api_key');
                return $k ? new MailKey(['label' => 'Legacy', 'provider' => 'mailersend', 'api_key' => $k]) : null;
            case 'sendpulse':
                $id = (string) Setting::get('mail.sendpulse.client_id');
                $sc = (string) Setting::get('mail.sendpulse.client_secret');
                return ($id && $sc) ? new MailKey(['label' => 'Legacy', 'provider' => 'sendpulse', 'api_key' => $id, 'api_secret' => $sc]) : null;
        }
        return null;
    }

    protected function markSuccess(MailKey $k): void
    {
        if (! $k->exists) return;
        $k->forceFill([
            'last_used_at' => now(),
            'last_error'   => null,
            'quota_used'   => $k->quota_used + 1,
        ])->save();
    }

    protected function markFailure(MailKey $k, string $msg): void
    {
        if (! $k->exists) return;
        $k->forceFill([
            'last_error'   => mb_substr($msg, 0, 500),
            'last_used_at' => now(),
        ])->save();
    }

    protected function dispatchProvider(string $provider, MailKey $key, array $recipients, string $subject, string $html, ?string $text): void
    {
        match ($provider) {
            'brevo'      => $this->sendViaBrevo($key, $recipients, $subject, $html, $text),
            'mailersend' => $this->sendViaMailerSend($key, $recipients, $subject, $html, $text),
            'sendpulse'  => $this->sendViaSendPulse($key, $recipients, $subject, $html, $text),
            default      => $this->sendViaSmtp($recipients, $subject, $html, $text),
        };
    }

    protected function sendViaSmtp(array $recipients, string $subject, string $html, ?string $text): bool
    {
        foreach ($recipients as $email => $name) {
            Mail::html($html, function ($m) use ($email, $name, $subject) {
                $m->to($email, $name ?: null)
                  ->from($this->fromEmail(), $this->fromName())
                  ->subject($subject);
            });
        }
        return true;
    }

    protected function sendViaBrevo(MailKey $key, array $recipients, string $subject, string $html, ?string $text): void
    {
        $payload = [
            'sender'      => ['email' => $this->fromEmail(), 'name' => $this->fromName()],
            'to'          => array_map(fn ($email, $name) => array_filter(['email' => $email, 'name' => $name]),
                                       array_keys($recipients), array_values($recipients)),
            'subject'     => $subject,
            'htmlContent' => $html,
        ];
        if ($text) $payload['textContent'] = $text;

        $res = Http::withHeaders(['api-key' => $key->api_key, 'accept' => 'application/json'])
            ->timeout(30)->asJson()->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $res->successful()) {
            throw new RuntimeException('Brevo '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
    }

    protected function sendViaMailerSend(MailKey $key, array $recipients, string $subject, string $html, ?string $text): void
    {
        $payload = [
            'from'    => ['email' => $this->fromEmail(), 'name' => $this->fromName()],
            'to'      => array_map(fn ($email, $name) => array_filter(['email' => $email, 'name' => $name]),
                                  array_keys($recipients), array_values($recipients)),
            'subject' => $subject,
            'html'    => $html,
        ];
        if ($text) $payload['text'] = $text;

        $res = Http::withToken($key->api_key)->timeout(30)->asJson()
            ->post('https://api.mailersend.com/v1/email', $payload);

        if (! $res->successful()) {
            throw new RuntimeException('MailerSend '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
    }

    protected function sendViaSendPulse(MailKey $key, array $recipients, string $subject, string $html, ?string $text): void
    {
        if (! $key->api_key || ! $key->api_secret) {
            throw new RuntimeException('SendPulse client_id/secret kosong.');
        }

        // Token (cache via setting per-key id agar aman saat banyak key)
        $cacheTokenKey = 'mail.sendpulse.token.'.($key->id ?? 'legacy');
        $cacheExpKey   = 'mail.sendpulse.token_expires.'.($key->id ?? 'legacy');
        $token = (string) Setting::get($cacheTokenKey);
        $exp = (int) Setting::get($cacheExpKey);

        if (! $token || $exp <= time() + 60) {
            $tokenRes = Http::timeout(30)->asJson()->post('https://api.sendpulse.com/oauth/access_token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $key->api_key,
                'client_secret' => $key->api_secret,
            ]);
            if (! $tokenRes->successful()) {
                throw new RuntimeException('SendPulse token gagal: '.mb_substr($tokenRes->body(), 0, 200));
            }
            $token = (string) $tokenRes->json('access_token');
            Setting::put($cacheTokenKey, $token, 'mail', true);
            Setting::put($cacheExpKey, (string) (time() + (int) $tokenRes->json('expires_in', 3600)), 'mail');
        }

        $email = [
            'html'    => base64_encode($html),
            'text'    => $text ?: strip_tags($html),
            'subject' => $subject,
            'from'    => ['name' => $this->fromName(), 'email' => $this->fromEmail()],
            'to'      => array_map(fn ($e, $n) => array_filter(['email' => $e, 'name' => $n]),
                                  array_keys($recipients), array_values($recipients)),
        ];

        $res = Http::withToken($token)->timeout(30)->asJson()
            ->post('https://api.sendpulse.com/smtp/emails', ['email' => $email]);

        if (! $res->successful()) {
            throw new RuntimeException('SendPulse '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
    }
}
