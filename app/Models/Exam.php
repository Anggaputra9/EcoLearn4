<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Exam extends Model
{
    protected $fillable = [
        'uuid',
        'material_id', 'classroom_id', 'teacher_id',
        'title', 'description',
        'duration_minutes', 'starts_at', 'ends_at', 'status',
        'prevent_tab_switch', 'max_tab_switch', 'prevent_copy_paste',
        'prevent_right_click', 'fullscreen_required', 'shuffle_questions',
        'grading_mode',
        'show_result_after_submit', 'show_leaderboard', 'allow_review_answer',
    ];

    /**
     * Pakai UUID sebagai route binding key, ID numerik tetap dipakai internal.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (self $exam) {
            if (empty($exam->uuid)) {
                $exam->uuid = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'starts_at'                => 'datetime',
        'ends_at'                  => 'datetime',
        'prevent_tab_switch'       => 'boolean',
        'prevent_copy_paste'       => 'boolean',
        'prevent_right_click'      => 'boolean',
        'fullscreen_required'      => 'boolean',
        'shuffle_questions'        => 'boolean',
        'show_result_after_submit' => 'boolean',
        'show_leaderboard'         => 'boolean',
        'allow_review_answer'      => 'boolean',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isOpenNow(): bool
    {
        if ($this->status !== 'published') return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }
}
