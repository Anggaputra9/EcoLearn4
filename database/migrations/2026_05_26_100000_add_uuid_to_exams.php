<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Tambah kolom UUID publik ke tabel exams sebagai key URL.
 * - Kolom internal `id` tetap dipakai untuk FK & relasi.
 * - `uuid` dipakai sebagai route key sehingga URL tidak menebak-nebak ID.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
        });

        // Backfill ujian yang sudah ada
        DB::table('exams')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('exams')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        // Setelah backfill, jadikan unique + non-null (SQLite-friendly: index unik saja).
        Schema::table('exams', function (Blueprint $table) {
            $table->unique('uuid', 'exams_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropUnique('exams_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
