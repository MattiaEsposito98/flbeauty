<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            // 'standard' = codice per tutti (come prima); 'welcome' = codice personale di benvenuto.
            $table->string('kind', 20)->default('standard')->after('code')->index();
            // Proprietario del codice personale: solo lui può usarlo. Se l'account viene eliminato
            // resta il codice (per lo storico dell'admin) ma nessuno può più usarlo.
            $table->foreignId('user_id')->nullable()->after('kind')->constrained()->nullOnDelete();
            // Quando è partita l'email col codice.
            $table->dateTime('emailed_at')->nullable()->after('ends_at');

            // Un solo codice di benvenuto per account (anche con due clic sul link di verifica).
            $table->unique(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'kind']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['kind', 'emailed_at']);
        });
    }
};
