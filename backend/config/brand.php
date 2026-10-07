<?php

/*
|--------------------------------------------------------------------------
| Dati del negozio usati nelle email
|--------------------------------------------------------------------------
|
| Gli stessi contatti del sito (frontend/src/config/contacts.js). Se cambiano,
| vanno aggiornati in entrambi i posti.
|
*/

return [
    'name' => 'F&L Beauty',
    'tagline' => 'Prodotti beauty scelti con cura',
    'email' => 'info@flbeauty.it',
    // Mittente delle email automatiche (verifica account, reset password, comunicazioni). Su Aruba
    // è un alias di info@ (nessuna casella da leggere); finché non esiste si usa il mittente
    // normale (MAIL_FROM_ADDRESS), così nessuna email viene rifiutata. Chi risponde scrive a info@.
    'noreply' => env('MAIL_NOREPLY_ADDRESS', env('MAIL_FROM_ADDRESS', 'info@flbeauty.it')),
    'whatsapp_display' => '351 745 9482',
    'whatsapp_url' => 'https://wa.me/393517459482',
    'tiktok' => [
        ['handle' => '@flbeauty', 'url' => 'https://www.tiktok.com/@flbeauty'],
        ['handle' => '@flbeauty2', 'url' => 'https://www.tiktok.com/@flbeauty2'],
    ],
];
