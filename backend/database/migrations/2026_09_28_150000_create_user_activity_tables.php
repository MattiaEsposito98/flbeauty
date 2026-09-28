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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('marketing_consent_at');
            $table->unsignedInteger('login_count')->default(0)->after('last_login_at');
        });

        // Storico accessi al sito (senza IP), per il grafico degli accessi.
        // Righe più vecchie di 12 mesi vengono cancellate (UserLogin::record()).
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('logged_in_at')->index();
        });

        // Banner cookie: solo conteggi giornalieri anonimi, nessun dato personale.
        Schema::create('cookie_consent_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('shown')->default(0);
            $table->unsignedInteger('accepted')->default(0);
            $table->unsignedInteger('rejected')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookie_consent_stats');
        Schema::dropIfExists('user_logins');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'login_count']);
        });
    }
};
