<?php

// Chiavi mancanti nella traduzione italiana di filament/filament.
// Laravel unisce questo file a quello del pacchetto, quindi basta l'integrazione.
return [

    'skip_to_content' => [
        'label' => 'Vai al contenuto',
    ],

    // Senza questa riga la campanella mostra la sigla tecnica al posto di «Notifiche, 2 da leggere».
    'actions' => [
        'open_database_notifications' => [
            'label_with_unread_count' => '{1} Notifiche, :count da leggere|[2,*] Notifiche, :count da leggere',
        ],
    ],

];
