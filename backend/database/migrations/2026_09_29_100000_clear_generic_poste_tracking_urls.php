<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Prima il form admin salvava nell'ordine la pagina di ricerca Poste senza
     * codice. Svuotandolo, il link viene ricostruito dal numero di tracking e
     * porta direttamente alla spedizione (Order::CARRIER_TRACKING_URLS).
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('tracking_url', 'https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni')
            ->update(['tracking_url' => null]);
    }

    public function down(): void
    {
        //
    }
};
