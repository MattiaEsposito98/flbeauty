<?php

use App\Models\ShippingRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Il tipo era un elenco chiuso (regione | ritiro): lo rendiamo testo libero per poter aggiungere
        // «a_mano» (ritiro di persona) senza altre modifiche al database.
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->string('type', 20)->default('regione')->change();
        });

        // La voce c'è subito dopo il deploy; l'admin può modificarla o disattivarla da Spedizioni.
        // Se esiste già (stesso nome e tipo) non viene toccata.
        ShippingRate::firstOrCreate(
            ['name' => 'Ritiro a mano (gratis)', 'type' => ShippingRate::TYPE_HAND],
            ['price' => 0, 'is_active' => true]
        );
    }

    public function down(): void
    {
        ShippingRate::where('type', ShippingRate::TYPE_HAND)->whereDoesntHave('orders')->delete();

        // Il tipo resta testo libero: tornare all'elenco chiuso fallirebbe se esistono ordini con «a_mano».
    }
};
