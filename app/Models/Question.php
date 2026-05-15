<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'prompt_text',
        'type',
        'max_score',
        'rubric',
        'options',
        'correct_option',
        'position',
    ];

    protected $casts = [
        'options'  => 'array',
        'position' => 'integer',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isMcq(): bool
    {
        return $this->type === 'mcq';
    }

    public function isEssay(): bool
    {
        return $this->type === 'essay';
    }

    /**
     * Daftar opsi MCQ yang sudah dinormalisasi (key + text).
     * @return array<int, array{key:string, text:string}>
     */
    public function normalizedOptions(): array
    {
        $opts = $this->options ?? [];
        $out = [];
        foreach ($opts as $i => $opt) {
            if (is_array($opt)) {
                $key = isset($opt['key']) ? (string) $opt['key'] : chr(65 + $i);
                $text = (string) ($opt['text'] ?? '');
            } else {
                $key = chr(65 + $i);
                $text = (string) $opt;
            }
            if ($text === '') continue;
            $out[] = ['key' => $key, 'text' => $text];
        }
        return $out;
    }
}
