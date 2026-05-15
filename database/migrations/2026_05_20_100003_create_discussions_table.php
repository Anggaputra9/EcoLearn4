<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained('materials')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['material_id', 'parent_id']);
            $table->index(['classroom_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussions');
    }
};
