<?php

// Chiave mancante nella traduzione italiana di filament/notifications: senza questa riga, nel pannello
// delle notifiche (la campanella dell'admin) ogni notifica da leggere mostra la sigla tecnica.
// Laravel unisce questo file a quello del pacchetto, quindi basta l'integrazione.
return [

    'modal' => [

        'unread_label' => 'Da leggere',

        'actions' => [

            'mark_all_as_read' => [
                'label' => 'Segna tutte come lette',
            ],

        ],

        'empty' => [
            'description' => 'Ricontrolla più tardi.',
        ],

    ],

];
