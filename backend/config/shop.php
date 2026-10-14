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

];
