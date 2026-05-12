<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiAIService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        // Mengambil API Key dari file .env demi keamanan
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Mengirim prompt ke Gemini dan mendapatkan balasan teks.
     */
    public function generateText($prompt)
    {
        // Menggunakan model gemini-3.1-flash-lite yang stabil
        $url = $this->baseUrl . 'gemini-3.1-flash-lite:generateContent?key=' . $this->apiKey;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        // Jika request berhasil
        if ($response->successful()) {
            return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada teks yang dikembalikan.';
        }

        // Jika terjadi error
        return 'Error API Gemini: ' . $response->body();
    }
}
