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
        'format',
        'content',
        'custom_prompt',
        'outputs',
        'is_published',
    ];

    protected $casts = [
        'is_published'   => 'boolean',
        'meeting_number' => 'integer',
        'outputs'        => 'array',
    ];

    /**
     * Daftar format yang didukung generator AI. Konsepnya seperti
     * NotebookLM: satu sumber materi bisa diolah jadi banyak varian
     * presentasi pengetahuan.
     *
     * @return array<string, array{label:string, icon:string, hint:string}>
     */
    public static function formats(): array
    {
        return [
            'standard' => [
                'label' => 'Materi Lengkap',
                'icon'  => 'book',
                'hint'  => 'Penjelasan terstruktur dengan pengantar, konsep kunci, refleksi & studi kasus.',
            ],
            'summary' => [
                'label' => 'Ringkasan',
                'icon'  => 'doc-text',
                'hint'  => 'Versi singkat berisi poin-poin inti yang mudah dibaca cepat.',
            ],
            'slides' => [
                'label' => 'Slide / PPT',
                'icon'  => 'monitor',
                'hint'  => 'Presentasi HTML kreatif (Tailwind); gambar di-scrape & disimpan permanen di server.',
            ],
            'infographic' => [
                'label' => 'Infografis',
                'icon'  => 'photo',
                'hint'  => 'Skema visual berbentuk teks: judul blok, ikon, fakta singkat.',
            ],
            'mindmap' => [
                'label' => 'Mind Map',
                'icon'  => 'chart',
                'hint'  => 'Hierarki cabang ide dari topik utama ke sub-konsep.',
            ],
            'flashcards' => [
                'label' => 'Flashcards',
                'icon'  => 'sparkles',
                'hint'  => 'Kartu tanya-jawab untuk mengingat istilah & konsep penting.',
            ],
            'lesson_plan' => [
                'label' => 'Rencana Pembelajaran',
                'icon'  => 'pencil',
                'hint'  => 'RPP singkat: tujuan, kegiatan, alokasi waktu, asesmen.',
            ],
        ];
    }

    public static function formatLabel(?string $key): string
    {
        $key = $key ?: 'standard';
        return self::formats()[$key]['label'] ?? ucfirst($key);
    }

    public static function formatIcon(?string $key): string
    {
        $key = $key ?: 'standard';
        return self::formats()[$key]['icon'] ?? 'doc-text';
    }

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
     * Mengembalikan daftar output (multi-format) untuk konsumsi view.
     * Selalu menyertakan format utama dari kolom `content` agar materi
     * lama (yang belum punya kolom outputs) tetap kelihatan.
     *
     * @return array<int, array{format:string, label:string, icon:string, content:string, html_path?:string, slides_url?:string}>
     */
    public function outputBundle(): array
    {
        $bundle = [];
        $seen = [];

        $enrich = function (array $item, array $source): array {
            if (($item['format'] ?? '') === 'slides' && ! empty($source['html_path'])) {
                $item['html_path'] = (string) $source['html_path'];
            }

            return $item;
        };

        $primaryFormat = $this->format ?: 'standard';
        $primarySource = ['html_path' => null];
        foreach ((array) $this->outputs as $out) {
            if (($out['format'] ?? '') === $primaryFormat) {
                $primarySource = $out;
                break;
            }
        }

        if (is_string($this->content) && trim($this->content) !== '') {
            $bundle[] = $enrich([
                'format'  => $primaryFormat,
                'label'   => self::formatLabel($primaryFormat),
                'icon'    => self::formatIcon($primaryFormat),
                'content' => (string) $this->content,
            ], $primarySource);
            $seen[$primaryFormat] = true;
        }

        foreach ((array) $this->outputs as $out) {
            $fmt = (string) ($out['format'] ?? 'standard');
            $txt = (string) ($out['content'] ?? '');
            $hasHtml = ($fmt === 'slides' && ! empty($out['html_path']));
            if (($txt === '' && ! $hasHtml) || isset($seen[$fmt])) {
                continue;
            }
            $bundle[] = $enrich([
                'format'  => $fmt,
                'label'   => (string) ($out['label'] ?? self::formatLabel($fmt)),
                'icon'    => self::formatIcon($fmt),
                'content' => $txt,
            ], $out);
            $seen[$fmt] = true;
        }

        return $bundle;
    }

    /**
     * Hitung nomor pertemuan berikutnya untuk seorang guru
     * dalam scope kelas tertentu (atau "tanpa kelas" kalau classroomId null).
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
