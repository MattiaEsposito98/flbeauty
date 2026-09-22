<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    /**
     * Regioni italiane con prezzo base di spedizione.
     * Sicilia e Sardegna (isole) hanno un costo maggiorato per via del trasporto via mare/aereo.
     */
    public function run(): void
    {
        $isole = ['Sicilia', 'Sardegna'];

        $regioni = [
            'Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia-Romagna',
            'Friuli-Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche',
            'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana',
            'Trentino-Alto Adige', 'Umbria', "Valle d'Aosta", 'Veneto',
        ];

        foreach ($regioni as $regione) {
            ShippingRate::updateOrCreate(
                ['name' => $regione, 'type' => 'regione'],
                ['price' => in_array($regione, $isole, true) ? 9.50 : 7.50, 'is_active' => true]
            );
        }

        ShippingRate::updateOrCreate(
            ['name' => 'Punto di ritiro (Italia e isole comprese)', 'type' => 'ritiro'],
            ['price' => 4.00, 'is_active' => true]
        );
    }
}
