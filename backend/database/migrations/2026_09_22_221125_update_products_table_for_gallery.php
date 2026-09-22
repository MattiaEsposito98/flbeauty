<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->json('images')->nullable()->after('description');
            $table->string('video')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['images', 'video']);
            $table->string('image')->nullable()->after('description');
        });
    }
};
