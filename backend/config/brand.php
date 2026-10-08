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
    // Mittente delle email automatiche (verifica account, reset password, comunicazioni). Su Aruba un
    // alias NON può inviare (errore 550): resta il mittente normale (MAIL_FROM_ADDRESS = info@) finché
    // non c'è una vera casella o un altro servizio. Chi risponde scrive sempre a info@ (vedi docs/EMAIL.md).
    'noreply' => env('MAIL_NOREPLY_ADDRESS', env('MAIL_FROM_ADDRESS', 'info@flbeauty.it')),
    'whatsapp_display' => '351 745 9482',
    'whatsapp_url' => 'https://wa.me/393517459482',
    'tiktok' => [
        ['handle' => '@fl.beauty', 'url' => 'https://www.tiktok.com/@fl.beauty'],
        ['handle' => '@fl_beauty2', 'url' => 'https://www.tiktok.com/@fl_beauty2'],
    ],
];
