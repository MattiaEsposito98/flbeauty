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
        Schema::table('comuni', function (Blueprint $table) {
            $table->string('province', 2)->nullable()->after('name');
            $table->string('region')->nullable()->after('province');
            $table->json('postal_codes')->nullable()->after('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comuni', function (Blueprint $table) {
            $table->dropColumn(['province', 'region', 'postal_codes']);
        });
    }
};
