<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan dukungan multi-output untuk generator materi (mirip konsep
 * NotebookLM): satu materi bisa punya beberapa varian format hasil AI
 * (ringkasan, slide, infografis, mind map, dll), plus prompt khusus dari
 * guru agar materi tidak hanya bertumpu pada "topik" yang luas.
 *
 *  - format        → format utama yang ditampilkan di kolom `content`
 *                    (standard | summary | slides | infographic |
 *                     mindmap | flashcards | lesson_plan)
 *  - custom_prompt → instruksi bebas dari guru, dipakai saat generate ulang
 *  - outputs       → JSON [{format, label, content}, ...] untuk semua
 *                    varian yang dihasilkan AI; bisa kosong untuk materi
 *                    lama/legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (! Schema::hasColumn('materials', 'format')) {
                $table->string('format', 32)->default('standard')->after('level');
            }
            if (! Schema::hasColumn('materials', 'custom_prompt')) {
                $table->text('custom_prompt')->nullable()->after('content');
            }
            if (! Schema::hasColumn('materials', 'outputs')) {
                // SQLite-friendly: pakai longText (json() di SQLite tetap di-encode).
                $table->longText('outputs')->nullable()->after('custom_prompt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            foreach (['outputs', 'custom_prompt', 'format'] as $col) {
                if (Schema::hasColumn('materials', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
