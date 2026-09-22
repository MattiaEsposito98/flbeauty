<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_rate_id')->nullable()->after('discount_id')->constrained()->nullOnDelete();
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('shipping_rate_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_rate_id');
            $table->dropColumn('shipping_cost');
        });
    }
};
