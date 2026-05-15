<?php

namespace App\Services;

use App\Models\AiKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AIService — provider-agnostic dengan rotasi multi-API key.
 *
 * Provider yang didukung:
 *   - gemini      (Google Gemini)
 *   - openai      (OpenAI Chat Completions)
 *   - anthropic   (Anthropic Messages)
 *   - openrouter  (OpenRouter, kompatibel OpenAI)
 *   - groq        (Groq, kompatibel OpenAI)
 *
 * Key disimpan di tabel ai_keys dengan kolom priority.
 * Saat key gagal/limit, akan otomatis berpindah ke key berikutnya.
 */
class AIService
{
    protected int $timeout;
    protected ?AiKey $usedKey = null;

    public function __construct()
    {
        $this->timeout = (int) config('services.gemini.timeout', 120);
    }

    public function lastUsedKey(): ?AiKey { return $this->usedKey; }

    public function defaultProvider(): string
    {
        return (string) (Setting::get('ai.default_provider') ?? 'gemini');
    }

    public function defaultModel(?string $provider = null): string
    {
        $provider = $provider ?: $this->defaultProvider();
        return (string) (Setting::get("ai.default_model.$provider") ?? match ($provider) {
            'openai'     => 'gpt-4o-mini',
            'anthropic'  => 'claude-3-5-haiku-20241022',
            'openrouter' => 'openrouter/auto',
            'groq'       => 'llama-3.1-70b-versatile',
            default      => 'gemini-2.0-flash',
        });
    }

    /** Daftar key aktif untuk provider, urut prioritas. */
    public function activeKeysFor(string $provider)
    {
        return AiKey::where('provider', $provider)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(function (AiKey $k) {
                $k->maybeResetQuota();
                return ! $k->isExhausted();
            })
            ->values();
    }

    public function hasAnyKey(): bool
    {
        return AiKey::where('is_active', true)->exists()
            || (string) (Setting::get('gemini.api_key') ?? config('services.gemini.api_key', '')) !== '';
    }

    public function generateText(string $prompt, ?string $systemInstruction = null): string
    {
        return $this->dispatch($prompt, $systemInstruction, /*json*/false);
    }

    public function generateJson(string $prompt, ?string $systemInstruction = null): array
    {
        $raw = $this->dispatch($prompt, $systemInstruction, /*json*/true);
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
        }
        if (! is_array($decoded)) {
            throw new RuntimeException('Respons AI bukan JSON valid: '.mb_substr($raw, 0, 200));
        }
        return $decoded;
    }

    /* ============================================================
     * Dispatcher: pilih provider lalu rotasi key
     * ============================================================ */
    protected function dispatch(string $prompt, ?string $sys, bool $wantJson): string
    {
        $provider = $this->defaultProvider();
        $keys = $this->activeKeysFor($provider);

        // Fallback ke key legacy di settings (gemini saja)
        if ($keys->isEmpty() && $provider === 'gemini') {
            $legacy = (string) (Setting::get('gemini.api_key') ?? config('services.gemini.api_key', ''));
            if ($legacy !== '') {
                $tmp = new AiKey([
                    'provider' => 'gemini',
                    'model'    => Setting::get('gemini.model') ?: $this->defaultModel('gemini'),
                    'api_key'  => $legacy,
                    'label'    => 'Legacy .env',
                ]);
                $keys = collect([$tmp]);
            }
        }

        if ($keys->isEmpty()) {
            throw new RuntimeException("Tidak ada API key aktif untuk provider [$provider]. Tambahkan dari halaman Konfigurasi AI.");
        }

        $errors = [];
        foreach ($keys as $key) {
            try {
                $model = $key->model ?: $this->defaultModel($provider);
                $text = match ($provider) {
                    'openai', 'openrouter', 'groq' => $this->callOpenAICompatible($provider, $key->api_key, $model, $prompt, $sys, $wantJson),
                    'anthropic'                    => $this->callAnthropic($key->api_key, $model, $prompt, $sys, $wantJson),
                    default                        => $this->callGemini($key->api_key, $model, $prompt, $sys, $wantJson),
                };

                $this->markSuccess($key);
                $this->usedKey = $key;
                return $text;
            } catch (\Throwable $e) {
                $errors[] = "[{$key->label}] ".$e->getMessage();
                $this->markFailure($key, $e->getMessage());
                Log::warning("AI key gagal ({$key->label}): ".$e->getMessage());
                // lanjut key berikutnya
            }
        }

        throw new RuntimeException('Semua API key gagal: '.implode(' | ', $errors));
    }

    protected function markSuccess(AiKey $k): void
    {
        if (! $k->exists) return;
        $k->forceFill([
            'last_used_at' => now(),
            'last_error'   => null,
            'quota_used'   => $k->quota_used + 1,
        ])->save();
    }

    protected function markFailure(AiKey $k, string $msg): void
    {
        if (! $k->exists) return;
        $k->forceFill([
            'last_error'   => mb_substr($msg, 0, 500),
            'last_used_at' => now(),
        ])->save();
    }

    /* ============================================================
     * Provider implementations
     * ============================================================ */
    protected function callGemini(string $apiKey, string $model, string $prompt, ?string $sys, bool $wantJson): string
    {
        $base = rtrim((string) config('services.gemini.base_url'), '/').'/';
        $url = $base.$model.':generateContent?key='.$apiKey;

        $payload = ['contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]]];
        if ($sys) $payload['systemInstruction'] = ['parts' => [['text' => $sys]]];
        if ($wantJson) {
            $payload['generationConfig'] = ['responseMimeType' => 'application/json', 'temperature' => 0.4];
        }

        $res = Http::timeout($this->timeout)->acceptJson()->asJson()->post($url, $payload);
        if (! $res->successful()) {
            throw new RuntimeException('Gemini '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
        $text = $res->json('candidates.0.content.parts.0.text');
        if (! is_string($text) || $text === '') {
            throw new RuntimeException('Gemini tidak mengembalikan teks.');
        }
        return $text;
    }

    protected function callOpenAICompatible(string $provider, string $apiKey, string $model, string $prompt, ?string $sys, bool $wantJson): string
    {
        $url = match ($provider) {
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'groq'       => 'https://api.groq.com/openai/v1/chat/completions',
            default      => 'https://api.openai.com/v1/chat/completions',
        };

        $messages = [];
        if ($sys) $messages[] = ['role' => 'system', 'content' => $sys];
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = ['model' => $model, 'messages' => $messages, 'temperature' => $wantJson ? 0.3 : 0.7];
        if ($wantJson) $payload['response_format'] = ['type' => 'json_object'];

        $res = Http::withToken($apiKey)->timeout($this->timeout)->acceptJson()->asJson()->post($url, $payload);
        if (! $res->successful()) {
            throw new RuntimeException(strtoupper($provider).' '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
        $text = $res->json('choices.0.message.content');
        if (! is_string($text) || $text === '') {
            throw new RuntimeException(strtoupper($provider).' tidak mengembalikan teks.');
        }
        return $text;
    }

    protected function callAnthropic(string $apiKey, string $model, string $prompt, ?string $sys, bool $wantJson): string
    {
        $url = 'https://api.anthropic.com/v1/messages';
        $payload = [
            'model'      => $model,
            'max_tokens' => 4096,
            'messages'   => [['role' => 'user', 'content' => $prompt.($wantJson ? "\n\nBalas hanya dengan JSON valid." : '')]],
        ];
        if ($sys) $payload['system'] = $sys;

        $res = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout($this->timeout)->acceptJson()->asJson()->post($url, $payload);

        if (! $res->successful()) {
            throw new RuntimeException('Anthropic '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
        $text = $res->json('content.0.text');
        if (! is_string($text) || $text === '') {
            throw new RuntimeException('Anthropic tidak mengembalikan teks.');
        }
        return $text;
    }

    /* ============================================================
     * Listing models (untuk Gemini saja, lainnya pakai daftar manual)
     * ============================================================ */
    public function listModels(string $provider = 'gemini'): array
    {
        if ($provider !== 'gemini') return $this->staticModelList($provider);

        $key = $this->activeKeysFor('gemini')->first();
        $apiKey = $key ? $key->api_key : (string) (Setting::get('gemini.api_key') ?? config('services.gemini.api_key', ''));
        if (! $apiKey) return $this->staticModelList('gemini');

        try {
            $url = rtrim((string) config('services.gemini.base_url'), '/').'?key='.$apiKey;
            $res = Http::timeout(20)->acceptJson()->get($url);
            if (! $res->successful()) return $this->staticModelList('gemini');
            return collect($res->json('models', []))
                ->map(fn ($m) => str_replace('models/', '', $m['name'] ?? ''))
                ->filter(fn ($n) => $n && str_contains($n, 'gemini'))
                ->values()->all();
        } catch (\Throwable $e) {
            return $this->staticModelList('gemini');
        }
    }

    public function staticModelList(string $provider): array
    {
        return match ($provider) {
            'openai'     => [
                'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini', 'gpt-4.1',
                'o3-mini', 'o4-mini',
            ],
            'anthropic'  => [
                'claude-3-5-haiku-20241022', 'claude-3-5-sonnet-20241022',
                'claude-3-7-sonnet-20250219', 'claude-3-opus-20240229',
            ],
            'openrouter' => [
                'openrouter/auto',
                'google/gemini-2.5-pro', 'google/gemini-2.5-flash',
                'google/gemini-2.0-flash-exp:free',
                'meta-llama/llama-3.1-70b-instruct',
            ],
            'groq'       => [
                'llama-3.3-70b-versatile', 'llama-3.1-70b-versatile',
                'llama-3.1-8b-instant', 'mixtral-8x7b-32768',
            ],
            default      => [
                // Gemini — termasuk seri terbaru (3.x) sampai legacy (1.5).
                // Pengguna juga bebas mengetik nama model lain (mis. preview / experimental).
                'gemini-3.0-pro',
                'gemini-3.0-flash',
                'gemini-3.0-flash-lite',
                'gemini-2.5-pro',
                'gemini-2.5-flash',
                'gemini-2.5-flash-lite',
                'gemini-2.0-pro',
                'gemini-2.0-flash',
                'gemini-2.0-flash-lite',
                'gemini-1.5-pro',
                'gemini-1.5-flash',
                'gemini-1.5-flash-8b',
            ],
        };
    }

    public function providers(): array

    {
        return [
            'gemini'     => 'Google Gemini',
            'openai'     => 'OpenAI',
            'anthropic'  => 'Anthropic Claude',
            'openrouter' => 'OpenRouter',
            'groq'       => 'Groq',
        ];
    }
}
