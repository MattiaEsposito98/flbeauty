<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro delle email inviate dal sito, per capire se partono o se c'è un errore.
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->index();      // inviata | errore
            $table->string('kind')->nullable();         // tipo di email (classe)
            $table->string('recipient', 500)->nullable()->index();
            $table->string('subject', 500)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
