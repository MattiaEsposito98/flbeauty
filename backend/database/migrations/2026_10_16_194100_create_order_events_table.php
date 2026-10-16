<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cronologia delle modifiche a un ordine (fatte dal cliente o dall'admin): serve all'admin per
        // vedere cosa è cambiato e a spedire al cliente l'avviso con le modifiche non ancora comunicate.
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('actor', 20);          // 'customer' | 'admin'
            $table->string('type', 30);           // 'edited' | 'cancelled'
            $table->json('summary');              // righe di testo: «Aggiunto: …», «Totale: … → …»
            $table->decimal('total_before', 10, 2)->nullable();
            $table->decimal('total_after', 10, 2)->nullable();
            // Quando il cliente è stato avvisato (email + notifica). Per le modifiche del cliente è subito.
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
    }
};
