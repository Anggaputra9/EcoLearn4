<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('title');
            $table->date('released_at');
            $table->longText('notes');
            $table->enum('kind', ['major', 'minor', 'patch', 'hotfix'])->default('minor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
