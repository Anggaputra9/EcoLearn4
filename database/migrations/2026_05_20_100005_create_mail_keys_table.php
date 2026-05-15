<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('provider', 40);              // brevo|mailersend|sendpulse|smtp
            $table->text('api_key')->nullable();         // untuk Brevo / MailerSend / SendPulse client_id
            $table->text('api_secret')->nullable();      // SendPulse client_secret
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('quota_limit')->nullable();
            $table->unsignedInteger('quota_used')->default(0);
            $table->timestamp('quota_reset_at')->nullable();
            $table->string('quota_reset_period', 20)->default('monthly');
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_keys');
    }
};
