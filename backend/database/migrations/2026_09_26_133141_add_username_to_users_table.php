<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Utenti già esistenti (es. l'admin) non hanno uno username: gliene
        // assegniamo uno provvisorio derivato dall'email per non lasciare righe rotte.
        DB::table('users')->whereNull('username')->orderBy('id')->each(function ($user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => Str::slug(explode('@', $user->email)[0]).'-'.$user->id]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
