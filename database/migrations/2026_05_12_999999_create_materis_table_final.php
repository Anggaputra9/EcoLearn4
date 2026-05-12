<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('materis', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path'); // Tempat menyimpan nama file PDF/Dokumen
            $table->unsignedBigInteger('guru_id'); // ID guru yang mengupload
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('materis');
    }
};
