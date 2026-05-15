<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('provider', 40);              // gemini|openai|anthropic|openrouter|groq
            $table->string('model', 120)->nullable();    // override model untuk key ini
            $table->text('api_key');                     // disimpan plain (boleh dienkripsi via cast)
            $table->unsignedInteger('priority')->default(0); // urutan pakai (kecil = duluan)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('quota_limit')->nullable();   // null = unlimited
            $table->unsignedInteger('quota_used')->default(0);
            $table->timestamp('quota_reset_at')->nullable();      // auto reset harian/bulanan
            $table->string('quota_reset_period', 20)->default('monthly'); // daily|monthly|none
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_keys');
    }
};
