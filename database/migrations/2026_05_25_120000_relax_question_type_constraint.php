<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom `questions.type` agar menerima nilai 'essay' & 'mcq'.
 *
 * Migrasi sebelumnya (`add_mcq_to_questions_and_submissions`) hanya menjalankan
 * ALTER MODIFY untuk MySQL — SQLite tetap memiliki CHECK constraint
 * `type IN ('essay')`, sehingga insert dengan 'mcq' melempar:
 *   "CHECK constraint failed: type".
 *
 * Solusi: konversi kolom ke string biasa (tanpa CHECK constraint). Ini aman
 * untuk semua database. Validasi nilai sudah ditangani di aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Untuk SQLite kita rebuild tabel manual karena `change()` pada kolom
        // dengan CHECK constraint sering tidak menghapus constraint lama.
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteQuestionsTable();
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->string('type', 16)->default('essay')->change();
        });
    }

    public function down(): void
    {
        // Tidak perlu mengembalikan CHECK constraint lama — itu justru
        // bug-prone. Biarkan kolom tetap string.
    }

    /**
     * SQLite tidak support DROP CHECK secara langsung. Cara aman:
     * 1) Buat tabel baru dengan skema yang diinginkan.
     * 2) Salin data.
     * 3) Drop tabel lama, rename tabel baru.
     */
    protected function rebuildSqliteQuestionsTable(): void
    {
        DB::transaction(function () {
            // Pastikan kolom-kolom MCQ sudah ada (dibuat oleh migrasi sebelumnya).
            $hasOptions  = Schema::hasColumn('questions', 'options');
            $hasCorrect  = Schema::hasColumn('questions', 'correct_option');
            $hasPosition = Schema::hasColumn('questions', 'position');

            // Sementara matikan FK agar bisa rename tanpa cascade.
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE questions__new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    material_id INTEGER NOT NULL,
                    prompt_text TEXT NOT NULL,
                    type VARCHAR(16) NOT NULL DEFAULT \'essay\',
                    max_score INTEGER NOT NULL DEFAULT 100,
                    rubric TEXT,
                    options TEXT,
                    correct_option VARCHAR(8),
                    position INTEGER NOT NULL DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
                )
            ');

            $cols = ['id', 'material_id', 'prompt_text', 'type', 'max_score', 'rubric'];
            if ($hasOptions)  $cols[] = 'options';
            if ($hasCorrect)  $cols[] = 'correct_option';
            if ($hasPosition) $cols[] = 'position';
            $cols[] = 'created_at';
            $cols[] = 'updated_at';

            $colList = implode(', ', $cols);
            DB::statement("INSERT INTO questions__new ({$colList}) SELECT {$colList} FROM questions");

            DB::statement('DROP TABLE questions');
            DB::statement('ALTER TABLE questions__new RENAME TO questions');

            DB::statement('PRAGMA foreign_keys = ON');
        });
    }
};
