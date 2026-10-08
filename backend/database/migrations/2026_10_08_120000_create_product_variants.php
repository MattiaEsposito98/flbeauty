<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Varianti di un prodotto (es. rossetto: rosso, verde, giallo). La disponibilità sta
        // sulla variante; products.stock diventa la somma delle varianti attive.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();          // vuoto = prezzo del prodotto
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Etichetta della scelta ("Colore", "Profumo", "Tonalità"...).
            $table->string('variant_label', 50)->nullable()->after('description');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
            // Nome della variante al momento dell'ordine: resta leggibile anche se poi si elimina.
            $table->string('variant_name')->nullable()->after('product_variant_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->cascadeOnDelete();

            // Prima di eliminare il vecchio indice (usato da user_id) ne serve uno nuovo.
            $table->unique(['user_id', 'product_id', 'product_variant_id'], 'cart_items_user_product_variant_unique');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_id_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_product_variant_unique');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            // Le righe con variante diventano doppioni: si tengono solo quelle senza.
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn('variant_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variant_label');
        });

        Schema::dropIfExists('product_variants');
    }
};
