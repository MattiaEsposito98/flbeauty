<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messaggi di validazione
    |--------------------------------------------------------------------------
    |
    | Traduzione italiana dei messaggi predefiniti di Laravel (lang/en). I nomi
    | dei campi sono in fondo, in `attributes`: "il campo telefono" invece di
    | "il campo address.phone".
    |
    */

    'accepted' => 'Il campo :attribute deve essere accettato.',
    'accepted_if' => 'Il campo :attribute deve essere accettato quando :other è :value.',
    'active_url' => 'Il campo :attribute deve essere un URL valido.',
    'after' => 'Il campo :attribute deve essere una data successiva al :date.',
    'after_or_equal' => 'Il campo :attribute deve essere una data uguale o successiva al :date.',
    'alpha' => 'Il campo :attribute può contenere solo lettere.',
    'alpha_dash' => 'Il campo :attribute può contenere solo lettere, numeri, trattini e trattini bassi.',
    'alpha_num' => 'Il campo :attribute può contenere solo lettere e numeri.',
    'any_of' => 'Il campo :attribute non è valido.',
    'array' => 'Il campo :attribute deve essere un elenco.',
    'ascii' => 'Il campo :attribute può contenere solo lettere, numeri e simboli semplici.',
    'before' => 'Il campo :attribute deve essere una data precedente al :date.',
    'before_or_equal' => 'Il campo :attribute deve essere una data uguale o precedente al :date.',
    'between' => [
        'array' => 'Il campo :attribute deve avere tra :min e :max elementi.',
        'file' => 'Il file :attribute deve pesare tra :min e :max kilobyte.',
        'numeric' => 'Il campo :attribute deve essere tra :min e :max.',
        'string' => 'Il campo :attribute deve contenere tra :min e :max caratteri.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'can' => 'Il campo :attribute contiene un valore non consentito.',
    'confirmed' => 'La conferma di :attribute non corrisponde.',
    'contains' => 'Il campo :attribute non contiene un valore obbligatorio.',
    'current_password' => 'La password non è corretta.',
    'date' => 'Il campo :attribute deve essere una data valida.',
    'date_equals' => 'Il campo :attribute deve essere una data uguale al :date.',
    'date_format' => 'Il campo :attribute deve avere il formato :format.',
    'decimal' => 'Il campo :attribute deve avere :decimal cifre decimali.',
    'declined' => 'Il campo :attribute deve essere rifiutato.',
    'declined_if' => 'Il campo :attribute deve essere rifiutato quando :other è :value.',
    'different' => 'I campi :attribute e :other devono essere diversi.',
    'digits' => 'Il campo :attribute deve essere di :digits cifre.',
    'digits_between' => 'Il campo :attribute deve avere tra :min e :max cifre.',
    'dimensions' => 'L\'immagine :attribute ha dimensioni non valide.',
    'distinct' => 'Il campo :attribute contiene un valore ripetuto.',
    'doesnt_contain' => 'Il campo :attribute non può contenere: :values.',
    'doesnt_end_with' => 'Il campo :attribute non può finire con: :values.',
    'doesnt_start_with' => 'Il campo :attribute non può iniziare con: :values.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'encoding' => 'Il campo :attribute deve essere codificato in :encoding.',
    'ends_with' => 'Il campo :attribute deve finire con uno di questi valori: :values.',
    'enum' => 'Il valore scelto per :attribute non è valido.',
    'exists' => 'Il valore scelto per :attribute non è valido.',
    'extensions' => 'Il file :attribute deve avere una di queste estensioni: :values.',
    'file' => 'Il campo :attribute deve essere un file.',
    'filled' => 'Il campo :attribute non può essere vuoto.',
    'gt' => [
        'array' => 'Il campo :attribute deve avere più di :value elementi.',
        'file' => 'Il file :attribute deve pesare più di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore di :value.',
        'string' => 'Il campo :attribute deve contenere più di :value caratteri.',
    ],
    'gte' => [
        'array' => 'Il campo :attribute deve avere almeno :value elementi.',
        'file' => 'Il file :attribute deve pesare almeno :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore o uguale a :value.',
        'string' => 'Il campo :attribute deve contenere almeno :value caratteri.',
    ],
    'hex_color' => 'Il campo :attribute deve essere un colore esadecimale valido.',
    'image' => 'Il campo :attribute deve essere un\'immagine.',
    'in' => 'Il valore scelto per :attribute non è valido.',
    'in_array' => 'Il campo :attribute deve essere presente in :other.',
    'in_array_keys' => 'Il campo :attribute deve contenere almeno una di queste chiavi: :values.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'ip' => 'Il campo :attribute deve essere un indirizzo IP valido.',
    'ipv4' => 'Il campo :attribute deve essere un indirizzo IPv4 valido.',
    'ipv6' => 'Il campo :attribute deve essere un indirizzo IPv6 valido.',
    'json' => 'Il campo :attribute deve essere un testo JSON valido.',
    'list' => 'Il campo :attribute deve essere un elenco.',
    'lowercase' => 'Il campo :attribute deve essere in minuscolo.',
    'lt' => [
        'array' => 'Il campo :attribute deve avere meno di :value elementi.',
        'file' => 'Il file :attribute deve pesare meno di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore di :value.',
        'string' => 'Il campo :attribute deve contenere meno di :value caratteri.',
    ],
    'lte' => [
        'array' => 'Il campo :attribute non può avere più di :value elementi.',
        'file' => 'Il file :attribute non può pesare più di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore o uguale a :value.',
        'string' => 'Il campo :attribute non può contenere più di :value caratteri.',
    ],
    'mac_address' => 'Il campo :attribute deve essere un indirizzo MAC valido.',
    'max' => [
        'array' => 'Il campo :attribute non può avere più di :max elementi.',
        'file' => 'Il file :attribute non può pesare più di :max kilobyte.',
        'numeric' => 'Il campo :attribute non può essere maggiore di :max.',
        'string' => 'Il campo :attribute non può contenere più di :max caratteri.',
    ],
    'max_digits' => 'Il campo :attribute non può avere più di :max cifre.',
    'mimes' => 'Il file :attribute deve essere di tipo: :values.',
    'mimetypes' => 'Il file :attribute deve essere di tipo: :values.',
    'min' => [
        'array' => 'Il campo :attribute deve avere almeno :min elementi.',
        'file' => 'Il file :attribute deve pesare almeno :min kilobyte.',
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'min_digits' => 'Il campo :attribute deve avere almeno :min cifre.',
    'missing' => 'Il campo :attribute non deve essere presente.',
    'missing_if' => 'Il campo :attribute non deve essere presente quando :other è :value.',
    'missing_unless' => 'Il campo :attribute non deve essere presente a meno che :other sia :value.',
    'missing_with' => 'Il campo :attribute non deve essere presente quando c\'è :values.',
    'missing_with_all' => 'Il campo :attribute non deve essere presente quando ci sono :values.',
    'multiple_of' => 'Il campo :attribute deve essere un multiplo di :value.',
    'not_in' => 'Il valore scelto per :attribute non è valido.',
    'not_regex' => 'Il formato del campo :attribute non è valido.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'password' => [
        'letters' => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed' => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers' => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols' => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Questa :attribute è comparsa in una fuga di dati: scegline un\'altra.',
    ],
    'present' => 'Il campo :attribute deve essere presente.',
    'present_if' => 'Il campo :attribute deve essere presente quando :other è :value.',
    'present_unless' => 'Il campo :attribute deve essere presente a meno che :other sia :value.',
    'present_with' => 'Il campo :attribute deve essere presente quando c\'è :values.',
    'present_with_all' => 'Il campo :attribute deve essere presente quando ci sono :values.',
    'prohibited' => 'Il campo :attribute non è consentito.',
    'prohibited_if' => 'Il campo :attribute non è consentito quando :other è :value.',
    'prohibited_if_accepted' => 'Il campo :attribute non è consentito quando :other è accettato.',
    'prohibited_if_declined' => 'Il campo :attribute non è consentito quando :other è rifiutato.',
    'prohibited_unless' => 'Il campo :attribute non è consentito a meno che :other sia tra: :values.',
    'prohibits' => 'Il campo :attribute non permette la presenza di :other.',
    'regex' => 'Il formato del campo :attribute non è valido.',
    'required' => 'Il campo :attribute è obbligatorio.',
    'required_array_keys' => 'Il campo :attribute deve contenere: :values.',
    'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
    'required_if_accepted' => 'Il campo :attribute è obbligatorio quando :other è accettato.',
    'required_if_declined' => 'Il campo :attribute è obbligatorio quando :other è rifiutato.',
    'required_unless' => 'Il campo :attribute è obbligatorio a meno che :other sia tra: :values.',
    'required_with' => 'Il campo :attribute è obbligatorio quando c\'è :values.',
    'required_with_all' => 'Il campo :attribute è obbligatorio quando ci sono :values.',
    'required_without' => 'Il campo :attribute è obbligatorio quando manca :values.',
    'required_without_all' => 'Il campo :attribute è obbligatorio quando mancano tutti questi: :values.',
    'same' => 'I campi :attribute e :other devono coincidere.',
    'size' => [
        'array' => 'Il campo :attribute deve contenere :size elementi.',
        'file' => 'Il file :attribute deve pesare :size kilobyte.',
        'numeric' => 'Il campo :attribute deve essere :size.',
        'string' => 'Il campo :attribute deve contenere :size caratteri.',
    ],
    'starts_with' => 'Il campo :attribute deve iniziare con uno di questi valori: :values.',
    'string' => 'Il campo :attribute deve essere un testo.',
    'timezone' => 'Il campo :attribute deve essere un fuso orario valido.',
    'unique' => 'Questo :attribute è già in uso.',
    'uploaded' => 'Il caricamento di :attribute non è riuscito.',
    'uppercase' => 'Il campo :attribute deve essere in maiuscolo.',
    'url' => 'Il campo :attribute deve essere un URL valido.',
    'ulid' => 'Il campo :attribute deve essere un ULID valido.',
    'uuid' => 'Il campo :attribute deve essere un UUID valido.',

    /*
    |--------------------------------------------------------------------------
    | Messaggi personalizzati per campo
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'username' => [
            'regex' => 'Lo username può contenere solo lettere, numeri, punto, trattino e trattino basso (niente spazi né @).',
            'unique' => 'Questo username è già in uso: scegline un altro.',
        ],
        'email' => [
            'unique' => 'Esiste già un account con questa email. Prova ad accedere o a recuperare la password.',
        ],
        'password' => [
            'confirmed' => 'Le due password non coincidono.',
        ],
        'address.postal_code' => [
            'size' => 'Il CAP deve essere di 5 cifre.',
        ],
        'postal_code' => [
            'size' => 'Il CAP deve essere di 5 cifre.',
        ],
        'items' => [
            'required' => 'Il carrello è vuoto.',
        ],
        'items.*.product_id' => [
            'distinct' => 'Lo stesso prodotto compare due volte nell\'ordine.',
            'exists' => 'Uno dei prodotti non è più disponibile.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nomi leggibili dei campi
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'nome e cognome',
        'username' => 'username',
        'email' => 'email',
        'login' => 'email o username',
        'password' => 'password',
        'token' => 'codice di verifica',
        'address' => 'indirizzo',
        'address.phone' => 'telefono',
        'address.address_line' => 'via e civico',
        'address.comune_id' => 'comune',
        'address.postal_code' => 'CAP',
        'phone' => 'telefono',
        'address_line' => 'via e civico',
        'comune_id' => 'comune',
        'postal_code' => 'CAP',
        'recipient_name' => 'destinatario',
        'label' => 'nome dell\'indirizzo',
        'is_default' => 'indirizzo principale',
        'address_id' => 'indirizzo di spedizione',
        'shipping_rate_id' => 'spedizione',
        'discount_code' => 'codice sconto',
        'items' => 'prodotti',
        'items.*.product_id' => 'prodotto',
        'items.*.quantity' => 'quantità',
        'product_id' => 'prodotto',
        'quantity' => 'quantità',
        'privacy_accepted' => 'informativa privacy',
        'marketing_consent' => 'consenso alle email promozionali',
    ],

];
