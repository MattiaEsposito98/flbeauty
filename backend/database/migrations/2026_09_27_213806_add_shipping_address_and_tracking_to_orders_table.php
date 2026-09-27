<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Copia dell'indirizzo al momento dell'ordine: resta valida anche se
            // il cliente modifica o elimina l'indirizzo dal proprio account.
            $table->string('shipping_address_line')->nullable()->after('customer_phone');
            $table->string('shipping_postal_code', 10)->nullable()->after('shipping_address_line');
            $table->string('shipping_city')->nullable()->after('shipping_postal_code');
            $table->string('shipping_province', 5)->nullable()->after('shipping_city');

            $table->string('carrier')->nullable()->after('status');
            $table->string('tracking_number')->nullable()->after('carrier');
            $table->string('tracking_url')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_address_line',
                'shipping_postal_code',
                'shipping_city',
                'shipping_province',
                'carrier',
                'tracking_number',
                'tracking_url',
            ]);
        });
    }
};
