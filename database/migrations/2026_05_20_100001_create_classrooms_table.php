<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('subject', 150)->nullable();
            $table->string('code', 12)->unique();           // kode untuk join
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('classroom_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['classroom_id', 'user_id']);
        });

        // Materi bisa terikat ke kelas (opsional → null = materi global)
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->after('teacher_id')
                  ->constrained('classrooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('classroom_id');
        });
        Schema::dropIfExists('classroom_members');
        Schema::dropIfExists('classrooms');
    }
};
