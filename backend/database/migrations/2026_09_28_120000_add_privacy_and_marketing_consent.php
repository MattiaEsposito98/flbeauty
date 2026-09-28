<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Utenti già esistenti: nessun consenso marketing (non l'hanno mai dato)
        // e `privacy_accepted_at` vuoto (registrati prima dell'informativa).
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_accepted_at')->nullable()->after('email_verified_at');
            $table->boolean('marketing_consent')->default(false)->after('privacy_accepted_at');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_consent');
        });

        // Le comunicazioni già inviate erano offerte/novità: marketing.
        Schema::table('communications', function (Blueprint $table) {
            $table->string('type')->default('marketing')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['privacy_accepted_at', 'marketing_consent', 'marketing_consent_at']);
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
