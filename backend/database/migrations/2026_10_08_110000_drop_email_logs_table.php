<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Il registro delle email è passato dal database al file di log (storage/logs/mail-*.log):
    // la tabella creata il 2026-10-08 non serve più.
    public function up(): void
    {
        Schema::dropIfExists('email_logs');
    }

    public function down(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->index();
            $table->string('kind')->nullable();
            $table->string('recipient', 500)->nullable()->index();
            $table->string('subject', 500)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }
};
