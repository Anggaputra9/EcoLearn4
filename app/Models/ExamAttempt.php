<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id', 'user_id', 'started_at', 'submitted_at', 'status',
        'tab_switch_count', 'cheat_log',
        'total_score', 'max_score', 'result_released',
    ];

    protected $casts = [
        'started_at'      => 'datetime',
        'submitted_at'    => 'datetime',
        'result_released' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function appendCheatLog(string $event): void
    {
        $log = json_decode((string) $this->cheat_log, true) ?: [];
        $log[] = ['t' => now()->toIso8601String(), 'event' => $event];
        $this->cheat_log = json_encode($log);
    }

    public function timeRemainingSeconds(): int
    {
        if (! $this->started_at || $this->exam->duration_minutes <= 0) return PHP_INT_MAX;
        $end = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
        return max(0, $end->diffInSeconds(now(), false) * -1);
    }
}
