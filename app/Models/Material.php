<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'title',
        'topic',
        'meeting_number',
        'level',
        'content',
        'is_published',
    ];

    protected $casts = [
        'is_published'   => 'boolean',
        'meeting_number' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class)->whereNull('parent_id')->latest();
    }

    /**
     * Hitung nomor pertemuan berikutnya untuk seorang guru
     * dalam scope kelas tertentu (atau "tanpa kelas" kalau classroomId null).
     *
     * Materi yang sudah di-soft-delete tetap diperhitungkan, supaya nomor
     * pertemuan tidak "terisi ulang" setelah materi dihapus → konsisten
     * dengan konsep histori.
     */
    public static function nextMeetingNumber(int $teacherId, ?int $classroomId): int
    {
        $max = static::withTrashed()
            ->where('teacher_id', $teacherId)
            ->when(
                $classroomId,
                fn ($q) => $q->where('classroom_id', $classroomId),
                fn ($q) => $q->whereNull('classroom_id')
            )
            ->max('meeting_number');

        return ((int) $max) + 1;
    }
}
