<?php

return [

    /*
    | Home del negozio: mix dei prodotti (vedi App\Support\HomeMix).
    | La categoria "in evidenza" occupa circa `featured_ratio` di ogni pagina, il resto è casuale
    | tra le altre categorie. Per cambiare categoria basta cambiare lo slug (in futuro: casella
    | "in evidenza" nel pannello).
    */
    'featured_category_slug' => env('SHOP_FEATURED_CATEGORY_SLUG', 'cosmetica'),

    'featured_ratio' => 0.6,

    /*
    | Codice di benvenuto: lo riceve ogni cliente quando verifica l'email (vedi
    | App\Support\WelcomeDiscount). Percentuale di sconto e giorni di validità dall'invio.
    */
    'welcome_discount_percent' => 15,
    'welcome_discount_days' => 30,

    /*
    | Notifiche nel sito (la campanella): ogni email ai clienti ha anche una notifica. Si cancellano
    | da sole dopo questi giorni (comando notifications:prune, ogni notte).
    */
    'notification_days' => 60,

    /*
    | Registro degli account eliminati dal cliente (App\Models\AccountDeletion): nome, email e date.
    | Serve a saperlo e a non ridare il codice di benvenuto a chi si riregistra. Si svuota da solo
    | dopo questi mesi (ogni notte). Va dichiarato nell'informativa privacy (frontend, Privacy.jsx).
    */
    'deletion_log_months' => 12,

];
