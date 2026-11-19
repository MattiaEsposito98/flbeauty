<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro degli account che il cliente si è eliminato dal sito: solo nome, email e date, per
     * sapere che è successo e per non dare un secondo codice di benvenuto a chi si riregistra.
     * Si svuota da solo dopo `shop.deletion_log_months` mesi (vedi AccountDeletion::prune()).
     */
    public function up(): void
    {
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();           // minuscolo e senza spazi
            $table->timestamp('registered_at')->nullable();
            $table->boolean('welcome_code_issued')->default(false);
            $table->timestamp('created_at')->useCurrent()->index();   // quando si è eliminato
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletions');
    }
};
