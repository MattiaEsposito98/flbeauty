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
        // SQLite (usato dai test automatici) non supporta gli indici full-text.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText(['name', 'description']);
        });
    }
};
