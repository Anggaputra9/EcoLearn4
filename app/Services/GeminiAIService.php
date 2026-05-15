<?php

namespace App\Services;

/**
 * @deprecated Gunakan App\Services\AIService.
 * Subclass ini ada hanya untuk backwards compatibility dengan kode lama.
 */
class GeminiAIService extends AIService
{
    /** Akses model default seperti API lama. */
    public function model(): string
    {
        return $this->defaultModel('gemini');
    }

    public function hasApiKey(): bool
    {
        return $this->hasAnyKey();
    }
}
