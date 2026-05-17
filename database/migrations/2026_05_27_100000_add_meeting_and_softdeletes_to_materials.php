<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom meeting_number (nomor pertemuan, contoh: 1,2,3,…)
 * dan soft-deletes pada tabel materials, supaya:
 *  - Guru bisa menentukan/iterate nomor pertemuan saat membuat materi.
 *  - Materi yang dihapus tidak benar-benar hilang (masuk "Histori"),
 *    bisa di-restore atau dihapus permanen kapan saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (! Schema::hasColumn('materials', 'meeting_number')) {
                $table->unsignedInteger('meeting_number')->nullable()->after('topic');
                $table->index(['teacher_id', 'classroom_id', 'meeting_number'], 'materials_meeting_idx');
            }
            if (! Schema::hasColumn('materials', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'meeting_number')) {
                try { $table->dropIndex('materials_meeting_idx'); } catch (\Throwable $e) {}
                $table->dropColumn('meeting_number');
            }
            if (Schema::hasColumn('materials', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
