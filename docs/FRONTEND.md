# Frontend — stato e decisioni

Ultimo aggiornamento: 2026-09-28

## Stack
- **React puro** (no Next.js, deciso con l'utente) scaffoldato con **Vite**
- `react-router-dom` per il routing, `axios` per le chiamate API
- Autenticazione: token Sanctum (Bearer), salvato in `localStorage`, allegato
  automaticamente dall'interceptor axios in `src/api/client.js`
- CORS lato backend già aperto di default per `api/*` (nessuna config aggiuntiva
  necessaria, verificato con `Access-Control-Allow-Origin: *`)

## Registrazione utente
Decisione presa con l'utente: la registrazione include **nome, username, email,
password** + un **indirizzo di spedizione obbligatorio**, presentato come
"il tuo indirizzo principale per le spedizioni" — l'utente potrà aggiungerne altri
in seguito dal proprio account.

- Backend: `AuthController::register` crea `User` + il primo `Address` (`is_default`)
  in un'unica transazione
- Frontend: `src/pages/Register.jsx`, un form unico diviso in due sezioni ("I tuoi
  dati" / "Indirizzo di spedizione")

## Login con email o username
Decisione presa con l'utente: nel form di accesso si può inserire **indifferentemente
l'email o lo username**, in un unico campo "Email o username".

- Backend: `POST /api/login` accetta ora il campo `login` (al posto di `email`) +
  `password`. La ricerca è in `User::findByLogin()`: se il valore contiene `@` cerca
  per email, altrimenti per username — nessuna ambiguità possibile perché la
  validazione di registrazione vieta `@` nello username (`regex:/^[a-zA-Z0-9._-]+$/`)
- Gli errori di login (credenziali errate, email non verificata) sono restituiti
  sotto la chiave `login`
- `POST /api/email/verification-notification` accetta anch'esso `login` (email o
  username), così "Invia di nuovo" funziona anche se l'utente ha provato ad
  accedere con lo username. La risposta resta generica
- Frontend: `Login.jsx` usa un campo `type="text"` con `autoComplete="username"`;
  `AuthContext.login(identifier, password)` e `resendVerificationEmail(identifier)`

## Indirizzi multipli
Dal proprio account (`src/pages/Account.jsx`) l'utente può aggiungere, modificare,
eliminare indirizzi e cambiare quale sia il "principale" (`is_default`). Un solo
indirizzo per utente può essere `is_default = true` — applicato lato modello
(`Address::booted()`, vedi `backend/app/Models/Address.php`).

## Tabella comuni italiani
Base dati (nome + coordinate) presa dalla tabella `cities` del DB locale `midalot`
(7894 comuni). Per non dipendere da quel DB locale (presente solo sulla macchina di
sviluppo), i dati sono stati **esportati una tantum** in
[`backend/database/data/comuni.tsv`](../backend/database/data/comuni.tsv)
(versionato nel repo) e importati tramite
[`ComuniSeeder`](../backend/database/seeders/ComuniSeeder.php).

**Arricchimento (sessione del 2026-09-26)**: provincia, regione e CAP sono stati
aggiunti incrociando per nome con il dataset open-source
[comuni-json di matteocontrini](https://github.com/matteocontrini/comuni-json)
(stessa fonte, dati ISTAT). Corrispondenza name-based: 7885/7894 automatica, le
restanti 9 (comuni con fusioni/rinomine recenti non ancora nel dataset, es.
"Moransengo-Tonengo", "Misiliscemi") risolte manualmente e documentate come
`$overrides` nello script di matching (non versionato, one-off — il risultato finale
è nel TSV). Colonne aggiunte alla tabella `comuni`: `province` (sigla, es. "MI"),
`region` (nome regione), `postal_codes` (array JSON — alcune città hanno più CAP,
es. Milano ne ha 42).

Endpoint di autocomplete: `GET /api/comuni?search=...`, restituisce anche
`province`/`postal_codes` per ogni comune.

### Provincia e CAP non sono più testo libero
- **Provincia**: derivata server-side dal comune scelto, mai dal client
  (`ResolvesComuneForAddress::resolveComuneFields()` in
  `backend/app/Http/Concerns/`, usato sia da `AuthController` che
  `AddressController`) — il campo nel form è disabilitato, mostra solo il valore
  derivato
- **CAP**: validato contro l'elenco dei CAP validi per quel comune. Nel form
  ([`PostalCodeField.jsx`](../frontend/src/components/PostalCodeField.jsx)):
  un solo CAP valido → campo bloccato e precompilato; più CAP validi (grandi
  città) → tendina di scelta; nessun comune scelto → campo disabilitato

## Modello dati indirizzo (`backend/app/Models/Address.php`)
Campi: `label` (es. "Casa"/"Ufficio"), `recipient_name`, `phone`, `address_line`,
`comune_id` (FK), `postal_code`, `province`, `is_default`.

## Catalogo, carrello, checkout
- **Catalogo** (`src/pages/Catalog.jsx`): prodotti attivi con filtro categoria, consuma
  `GET /api/products` e `GET /api/categories` (pubblici, no auth)
- **Dettaglio prodotto** (`src/pages/ProductDetail.jsx`): `GET /api/products/{slug}`
- **Carrello** (`src/context/CartContext.jsx`): stato salvato in `localStorage` (
  sopravvive a refresh/logout), nessuna chiamata API finché non si va al checkout
- **Checkout** (`src/pages/Checkout.jsx`, richiede login): sceglie indirizzo salvato +
  tariffa di spedizione + codice sconto opzionale, invia `POST /api/orders`
- **Conferma ordine** (`src/pages/OrderDetail.jsx`): mostra il totale reale calcolato
  dal backend (con sconto/spedizione applicati)

### Backend — creazione ordine (`OrderController::store`)
- Transazione con lock (`lockForUpdate`) sui prodotti per evitare overselling in caso
  di richieste concorrenti
- Verifica stock disponibile per ogni riga, **decrementa lo stock** alla conferma
  (funzionalità che nell'admin ancora mancava del tutto prima di questa sessione)
- Verifica validità del codice sconto (attivo, dentro le date `starts_at`/`ends_at`)
- **Bug corretto in questa sessione**: `Order::recalculateTotal()` non applicava mai lo
  sconto al totale (né da admin né da API) — ora sottrae correttamente l'importo dello
  sconto prima di sommare la spedizione
- Invia subito una **email di conferma ordine** (`App\Mail\OrderConfirmation`, nuova
  in questa sessione — prima esisteva solo l'email di cambio stato, non quella di
  conferma all'acquisto)

### Spedizione automatica per regione (implementato)
Le tariffe di spedizione in admin sono definite **per regione italiana** (Abruzzo,
Lombardia, ecc.), e `comuni.region` coincide col nome esatto usato in
`shipping_rates.name` per le tariffe di tipo "regione".

**Nota di normalizzazione**: il dataset open-source usa nomi bilingue per due regioni
("Trentino-Alto Adige/Südtirol", "Valle d'Aosta/Vallée d'Aoste"), diversi dalla
convenzione già in uso in `shipping_rates` — normalizzati alla forma semplice
direttamente in `comuni.tsv` per far combaciare i nomi esattamente.

In [`Checkout.jsx`](../frontend/src/pages/Checkout.jsx): quando si sceglie/cambia
l'indirizzo, la tariffa la cui `name` corrisponde alla `region` del comune
dell'indirizzo viene selezionata automaticamente (tariffe di tipo `regione`), con un
messaggio che lo segnala ("Tariffa suggerita in base alla regione..."). L'utente può
comunque cambiarla manualmente in qualunque momento (es. per scegliere "Punto di
ritiro"): la select resta sempre modificabile, non è un vincolo, solo un default
intelligente. Testato con un indirizzo a Napoli → seleziona automaticamente
"Campania".

## Storico ordini nell'account
`src/pages/Account.jsx` mostra ora anche l'elenco degli ordini dell'utente (numero,
stato, data, totale), ognuno con link al dettaglio (`/ordini/{id}`). Consuma
`GET /api/orders` (già esistente, non serviva nulla di nuovo lato backend).

**Bug corretto in questa sessione**: `OrderDetail.jsx` cercava `order.shippingRate`
(camelCase) ma Eloquent serializza le relazioni in **snake_case**
(`order.shipping_rate`) — la spedizione non veniva mai mostrata nel dettaglio ordine,
ora corretto.

## Homepage = catalogo
Decisione presa con l'utente: la prima pagina che vede chi arriva sul sito è
direttamente il catalogo (`/`), non una landing generica — così il visitatore vede
subito i prodotti. `src/pages/Home.jsx` è stato rimosso (non serviva più); la rotta
`/catalogo` resta come redirect verso `/` per compatibilità.

## Carrello: sopravvive a login/registrazione/logout
Il carrello vive solo in `localStorage` (`src/context/CartContext.jsx`), completamente
indipendente dallo stato di autenticazione — nessun codice lo svuota durante
login/register/logout, solo dopo un ordine completato con successo (`clearCart()` in
`Checkout.jsx`). Testato dal vivo: prodotto aggiunto al carrello → registrazione →
verifica email → login → il carrello risulta ancora intatto.

## Verifica email obbligatoria (nuovo)
Decisione presa con l'utente: dopo la registrazione l'utente **non viene loggato
automaticamente** — deve prima verificare l'indirizzo email cliccando il link
ricevuto, altrimenti il login viene rifiutato con un messaggio esplicito.

Backend:
- `User` implementa `MustVerifyEmail` (il trait è già incluso dalla classe base
  `Illuminate\Foundation\Auth\User`, bastava l'interfaccia — vedi
  `backend/app/Models/User.php`)
- `AuthController::register`: crea l'utente, invia l'email di verifica
  (`sendEmailVerificationNotification()`), **non** restituisce un token
- `AuthController::login`: se `hasVerifiedEmail()` è falso, rifiuta con 422 e il
  messaggio "Devi verificare la tua email prima di accedere..."
- `EmailVerificationController::verify` — rotta pubblica firmata (`signed`)
  `GET /api/email/verify/{id}/{hash}`, aperta dal link nell'email (non passa dalla
  SPA React): segna l'email verificata e fa un redirect 302 verso
  `{FRONTEND_URL}/login?verified=1`
- `EmailVerificationController::resend` — `POST /api/email/verification-notification`,
  pubblica, risposta sempre generica per non rivelare se un'email è registrata
- Testo dell'email personalizzato in italiano via `VerifyEmail::toMailUsing()` in
  `AppServiceProvider::boot()` (di default sarebbe stato in inglese)
- Nuova variabile d'ambiente `FRONTEND_URL` (in `.env`/`.env.example` e
  `config('app.frontend_url')`) usata solo per il redirect post-verifica

Frontend:
- `Register.jsx`: dopo la registrazione mostra "Controlla la tua email" invece di
  entrare nell'account
- `Login.jsx`: mostra il banner "Email verificata!" quando arriva da
  `?verified=1`; se il login fallisce per email non verificata, mostra un link
  "Invia di nuovo" che richiama il resend

Testato dal vivo l'intero ciclo: registrazione → email arrivata su Mailpit → login
rifiutato → reinvio → click sul link di verifica → redirect a login con banner →
login riuscito.

## Ordini solo per utenti registrati (già garantito)
Non serviva nessuna modifica: `POST /api/orders` è già dentro il gruppo di rotte
`middleware('auth:sanctum')`, e la pagina `/checkout` è già avvolta in
`ProtectedRoute` (redirect a `/login` se non autenticato). Non esiste e non è mai
esistito un percorso di checkout "guest" nel frontend.

## Quando viene scalato lo stock dei prodotti
Nel momento esatto in cui l'utente clicca **"Conferma ordine"** nel checkout — cioè
alla chiamata `POST /api/orders` (`OrderController::store`), non prima (aggiungere al
carrello non tocca lo stock) e non dopo (non serve un'azione admin successiva). Lo
stock viene decrementato dentro la stessa transazione che crea l'ordine, con
`lockForUpdate()` sui prodotti per evitare che due checkout simultanei vendano più
pezzi di quelli disponibili.

## Recupero password (nuovo, sessione del 2026-09-27)
Flusso standard "password dimenticata", separato dalla verifica email:

- `POST /api/forgot-password` (pubblica, `email`): risposta sempre generica per
  non rivelare se l'indirizzo è registrato. Usa il `PasswordBroker` nativo di
  Laravel (`password_reset_tokens`, già presente dalle migration di default).
- `POST /api/reset-password` (pubblica, `token`/`email`/`password`/
  `password_confirmation`): se il token è valido aggiorna la password, altrimenti
  errore 422 (link scaduto/non valido).
- Email personalizzata (stesso pattern di `VerifyEmail`, vedi
  `AppServiceProvider::boot()`): `ResetPassword::createUrlUsing()` genera un link
  diretto al **frontend** (`{FRONTEND_URL}/reimposta-password?token=...&email=...`),
  non un redirect via backend come per la verifica email — qui è la SPA React a
  raccogliere la nuova password e chiamare l'API, non serve una rotta firmata lato
  Laravel.
- Frontend: `src/pages/ForgotPassword.jsx` (richiesta link), `src/pages/
  ResetPassword.jsx` (legge `token`/`email` dalla query string, imposta la nuova
  password, poi redirect a `/login?reset=1` con banner di conferma). Link "Password
  dimenticata?" aggiunto in `Login.jsx`.

Testato dal vivo: richiesta dalla SPA → email ricevuta su Mailpit → click sul link
→ nuova password impostata → redirect a login con banner.

## Ricerca prodotti (nuovo, sessione del 2026-09-27)
Il backend supportava già `GET /api/products?search=...` ma cercava solo nel
`name` e il frontend non aveva alcun campo di ricerca (solo filtro categoria).

- Backend (`ProductController::index`): il parametro `search` ora cerca anche in
  `description`, non solo in `name`.
- Frontend (`Catalog.jsx`): aggiunta una barra di ricerca testuale, sincronizzata
  con il query param `?q=` (debounce di 400ms per non chiamare l'API ad ogni
  tasto premuto), combinabile col filtro categoria — cambiare categoria non
  cancella più il testo cercato. Testato dal vivo: ricerca "lenzuolo" → mostra
  solo "Lenzuolo bianco".

## Paginazione catalogo (nuovo, sessione del 2026-09-27)
Il backend pagina già server-side a 12 prodotti (`Product::paginate(12)` in
`ProductController::index`) — corretto per le performance: anche con un catalogo
grande, il payload resta piccolo e la query resta veloce indipendentemente dal
numero totale di prodotti. Il gap era solo lato frontend, che leggeva `data.data`
ignorando `data.meta` (paginazione mai raggiungibile oltre la prima pagina).

- `Catalog.jsx` ora legge anche `data.meta` (`current_page`/`last_page`) e
  sincronizza la pagina corrente con il query param `?page=`
- Pulsanti "Precedente"/"Successiva" + indicatore "Pagina X di Y", visibili solo
  se `last_page > 1`
- Cambiare categoria o cercare un prodotto resetta sempre alla pagina 1 (il
  parametro `page` viene rimosso dall'URL)
- Testato dal vivo: 14 prodotti totali → pagina 1 mostra 12 prodotti + link
  "Successiva" → pagina 2 mostra i restanti 2 con "Successiva" disabilitato →
  cambiare categoria da pagina 2 riporta a pagina 1 con l'URL pulito

## Indice full-text sulla ricerca prodotti (nuovo, sessione del 2026-09-27)
La ricerca usava `LIKE '%...%'` su `name`/`description` (nessun indice, scan
completo della tabella). Aggiunto in anticipo un indice **FULLTEXT** MySQL così
la ricerca resta veloce anche quando il catalogo crescerà molto, senza dover
rifare questo lavoro più avanti.

- Migration: `add_fulltext_index_to_products_table` — `FULLTEXT(name, description)`
- `ProductController::index()`: la query di ricerca ora usa `whereFullText()` in
  modalità *boolean*, con ogni parola trasformata in un prefisso obbligatorio
  (`+parola*`) per restare il più vicino possibile al comportamento precedente
  (ricerca "a partire da", non serve digitare la parola per intero)
- **Trade-off accettato**: FULLTEXT indicizza parole intere, quindi matcha
  parola-intera e prefissi (`lenz` → "Lenzuolo"), ma **non** sottostringhe a metà
  parola (`zuolo` non matcha più "Lenzuolo") — limite intrinseco di MySQL
  FULLTEXT, non risolvibile senza un indice trigram dedicato (non necessario ora)
- Testato dal vivo: `lenzuolo` (parola intera) ✓, `lenz` (prefisso) ✓, `fantasma`
  (nella descrizione) ✓, `zuolo` (suffisso) → nessun risultato, come atteso

## Wishlist / preferiti (nuovo, sessione del 2026-09-27)
Lista dei prodotti preferiti persistita **lato server** per utente (non
`localStorage` come il carrello) — coerente con gli indirizzi, così i preferiti
seguono l'utente su ogni dispositivo.

- Tabella `wishlist_items` (`user_id`, `product_id`, unique su entrambe)
- `WishlistController`: `GET /api/wishlist` (prodotti completi, non solo gli id
  — serve sia per il cuoricino attivo/spento sia per la pagina Preferiti senza
  una seconda chiamata), `POST/DELETE /api/wishlist/{product}` (idempotenti,
  `firstOrCreate`/`delete` diretto)
- Frontend: `WishlistContext` carica la lista al login (si svuota al logout),
  `WishlistButton` (cuoricino ♡/♥) riusabile in `ProductCard` e `ProductDetail`
  — se l'utente non è loggato, il click redirige al login (i preferiti sono solo
  per utenti registrati, come il checkout)
- Nuova pagina `/preferiti` (protetta), link "Preferiti" in navbar solo per
  utenti loggati

## Mini-carrello a scomparsa + indicatore "già nel carrello" (nuovo, sessione del 2026-09-27)
Decisione presa con l'utente: invece di dover aprire `/carrello` per capire cosa
si ha già selezionato, un pannello laterale (stile Amazon) mostra il contenuto
del carrello senza lasciare la pagina corrente.

- `CartContext`: nuovo stato `drawerOpen` — si apre automaticamente ad ogni
  `addItem()` e si può riaprire/chiudere manualmente; nuova funzione
  `getQuantityInCart(productId)`
- `CartDrawer.jsx`: pannello fisso a destra (overlay + sfondo scuro cliccabile
  per chiudere, più pulsante "×"), quantità modificabile, totale, link al
  carrello completo (`/carrello`, pagina esistente, invariata) e "Procedi
  all'ordine". Montato una volta in `App.jsx`, visibile su qualunque pagina
- Il pulsante "Carrello" in navbar ora apre il pannello invece di navigare
- **Indicatore "già nel carrello"**: badge "Nel carrello (N)" su ogni
  `ProductCard` nel catalogo e "Già nel carrello: N" nel dettaglio prodotto —
  visibile senza dover aprire il carrello, come richiesto
- Testato dal vivo: aggiunta prodotto dalla pagina dettaglio → pannello si apre
  da solo → chiusura manuale → badge "Carrello (1)" resta visibile in navbar →
  riapertura dal pulsante navbar

## Carrello allineato al server per utenti loggati (nuovo, sessione del 2026-09-27)
Prima il carrello viveva solo in `localStorage` (perso cambiando dispositivo).
Allineato allo stesso pattern della wishlist: **per gli ospiti resta in
localStorage** (nessun account a cui agganciarlo), **per gli utenti loggati vive
sul server** (tabella `cart_items`, come `wishlist_items`) e li segue su ogni
dispositivo.

- Tabella `cart_items` (`user_id`, `product_id`, `quantity`, unique su
  `user_id`+`product_id`)
- `CartController`: `GET /api/cart` (lista con prodotto completo, stesso
  approccio della wishlist), `POST /api/cart` (aggiunge/incrementa),
  `PATCH /api/cart/{product}` (imposta quantità esatta, usato dall'input
  numerico), `DELETE /api/cart/{product}` (rimuove una riga),
  `DELETE /api/cart` (svuota tutto, usato dopo un ordine completato)
- **Merge automatico al login**: se l'utente aveva un carrello da ospite in
  `localStorage`, viene sommato a quello già salvato sull'account (una singola
  volta per sessione di login, tracciata con un `ref` per evitare merge
  duplicati se il componente si ri-renderizza), poi il carrello locale viene
  svuotato e lo stato riflette sempre e solo il server
- **Cambio di comportamento rispetto a prima**: il carrello non sopravvive più
  al logout come faceva quando viveva solo in `localStorage` — dopo il logout
  riparte vuoto (come carrello "ospite"), ma riappare intatto al login
  successivo perché nel frattempo è salvato sull'account. È il trade-off
  corretto per avere la persistenza multi-dispositivo
- Nessuna modifica necessaria a `Cart.jsx`, `CartDrawer.jsx`, `ProductCard.jsx`,
  `Checkout.jsx`: consumano tutti `items`/`addItem`/ecc. da `CartContext`, che
  incapsula la differenza ospite/account
- Testato dal vivo: aggiunto un prodotto da ospite → login → merge (badge
  "Carrello (1)" in navbar) → logout → badge sparisce → login di nuovo →
  "Carrello (1)" ricompare dal server

## Restyling grafico completo (nuovo, sessione del 2026-09-27)
Tema "boutique beauty" accogliente e femminile (pubblico ~90% femminile), con i
colori dell'admin/logo. Regole, token e componenti sono documentati in
[DESIGN.md](DESIGN.md) — da rispettare per ogni modifica futura.

Novità visibili: barra annuncio + header sticky con icone e badge (preferiti,
carrello), hero sul catalogo con ricerca integrata, card prodotto con link su tutta
la card, pagina prodotto con galleria miniature e stepper quantità, carrello
laterale e pagina carrello a due colonne, checkout a sezioni con riepilogo
laterale, pagine di accesso su card con l'emblema del logo, account con avatar
ottagonale, footer con contatti (WhatsApp 351 745 9482, Flbeauty32@gmail.com,
TikTok @flbeauty e @flbeauty2) e pulsante WhatsApp flottante.

Piccoli cambi di comportamento arrivati col restyling:
- "Esci" non è più nell'header ma nella pagina account (nell'header resta l'icona
  utente, che porta all'account o al login)
- Il cuoricino in header è visibile anche agli ospiti: porta al login, e dopo
  l'accesso si torna alla pagina richiesta (`ProtectedRoute` ora passa
  `?redirect=`)
- Cambiando pagina si riparte dall'alto (`ScrollToTop` in `App.jsx`); cambiando
  pagina del catalogo si scorre all'inizio della griglia
- `CartContext` espone `loading`: carrello e checkout mostrano un caricamento
  finché il carrello dell'account non arriva dal server (prima compariva per un
  istante "carrello vuoto")
- **Bug corretto**: un utente senza indirizzi vedeva "Caricamento indirizzi..."
  all'infinito nel checkout; ora vede un invito ad aggiungerne uno
- **Bug corretto**: in registrazione gli errori sulla password (es. meno di 8
  caratteri, conferma diversa) non venivano mostrati
- Immagini prodotto mancanti o non raggiungibili mostrano il placeholder del brand
  invece dell'icona "immagine rotta" (`ProductImage`). Nota: il prodotto "Lenzuolo
  bianco" nel DB locale punta a file che non esistono più in `storage/` — va
  ricaricata l'immagine dall'admin
- Rimossi `public/favicon.svg` e `public/icons.svg` (residui del template Vite),
  sostituiti dal favicon del brand; titolo pagina "F&L Beauty" e `lang="it"`

Testato dal vivo a 1280 px e 375 px: catalogo, card con badge carrello/preferiti,
pagina prodotto, carrello laterale (apertura all'aggiunta, chiusura con Esc),
pagina carrello con stepper, checkout con tariffa suggerita, login, registrazione,
account con aggiunta indirizzo.

## Acquisto più rapido e limiti di stock (nuovo, sessione del 2026-09-27)
Decisioni prese con l'utente per rendere l'aggiunta al carrello meno macchinosa:
- **Aggiunta dal catalogo**: ogni card ha un pulsante "Aggiungi"; se il prodotto è
  già nel carrello diventa uno stepper − N + sulla card stessa (a 0 il prodotto
  esce dal carrello). Il click sul resto della card apre sempre i dettagli
- **Avviso invece del pannello**: dal catalogo non si apre il carrello laterale
  (interromperebbe chi aggiunge più prodotti), compare un avviso in basso
  (`CartToast`, 3,5 s, con "Vedi carrello"). Dalla pagina prodotto il pannello si
  apre come prima (`addItem(product, qty, { openDrawer: false })` per l'avviso)
- **Pannello laterale**: aggiunti "Continua lo shopping" (chiude il pannello; da una
  pagina prodotto riporta al catalogo) e "Svuota carrello"
- **Svuota carrello** anche nella pagina carrello; conferma nel pulsante stesso
  (`ConfirmButton`: primo click "Sicuro? Svuota", si annulla dopo 3 s) invece del
  popup del browser
- **Scorte**: sopra 5 pezzi si mostra solo "Disponibile", da 5 in giù "Ultimi N
  pezzi" (card e pagina prodotto). Soglia in `LOW_STOCK_THRESHOLD`
  (`src/utils/format.js`), il numero esatto del magazzino non viene esposto
- **Bug corretto**: si potevano mettere nel carrello più pezzi dello stock
  (l'errore usciva solo alla conferma dell'ordine). Ora `addItem`/`updateQuantity`
  si fermano allo stock, la pagina prodotto limita la quantità ai pezzi non ancora
  nel carrello ("Hai già nel carrello tutti i pezzi disponibili") e anche
  `CartController` (store/update) tronca la quantità allo stock lato server

### Avvisi quando la disponibilità limita il carrello
Richiesta dell'utente dopo un ordine in cui 50 pezzi erano stati ridotti a 26 in
silenzio: ora il cliente viene sempre avvisato e conferma con le quantità nuove.
- **Limite scattato mentre si aggiunge/modifica**: avviso arancione "Disponibili solo
  N pezzi di …" (`CartToast` con `type: 'warning'`), sia quando il limite lo applica
  il client sia quando è il server a troncare (`checkServerLimit` confronta la
  quantità salvata con quella richiesta)
- **Ricontrollo all'apertura** di carrello, pannello laterale e checkout
  (`syncAvailability()` in `CartContext`): legge stock e prezzi aggiornati da
  `GET /api/products/availability?ids=…` (pubblico, funziona anche per gli ospiti;
  i prodotti disattivati non vengono restituiti e sono trattati come esauriti),
  riduce le quantità oltre lo stock e rimuove gli esauriti, e mostra
  `CartAdjustmentsNotice` ("ne restano 26 (ne avevi 50)" / "esaurito, rimosso")
- **Stock cambiato proprio al click su "Conferma ordine"**: se l'API rifiuta per
  disponibilità, il checkout riallinea il carrello, mostra le modifiche e chiede di
  confermare di nuovo (l'ordine non parte con quantità diverse da quelle viste)

Testato dal vivo da ospite con un carrello "vecchio" (40 pezzi con 32 disponibili,
un prodotto nel frattempo esaurito): quantità ridotta a 32, esaurito rimosso,
riquadro con l'elenco delle modifiche.

Testato dal vivo: aggiunta dalla card (avviso, nessun pannello, badge aggiornato),
stepper fermo a 5 su un prodotto con stock 5, messaggio nella pagina prodotto,
svuotamento con conferma, "Continua lo shopping" dalla pagina prodotto → catalogo,
layout della card a 375 px.

## Flusso ordini senza pagamento online (nuovo, sessione del 2026-09-27)
Il pagamento avviene fuori dal sito (Postepay, istruzioni da definire): per l'admin
un ordine è davvero confermato solo quando arrivano i soldi.

| Stato (admin) | Cliente vede | Significato | Stock |
|---|---|---|---|
| Nuovo | In attesa di pagamento | Ordine ricevuto, pagamento non ancora arrivato | Scalato alla conferma (riservato) |
| In lavorazione | In lavorazione | Pagato, in preparazione | Resta scalato |
| Evaso | Evaso | Spedito: ordine concluso | Resta scalato |
| Annullato | Annullato | Non pagato / non valido | Torna disponibile |

Regole di stock centralizzate nei modelli (valgono per sito e admin allo stesso
modo): `OrderItem` scala/restituisce la differenza quando una riga viene creata,
modificata o eliminata; `Order` restituisce tutto quando passa ad "annullato",
riscala quando un annullato viene riattivato e restituisce tutto se l'ordine viene
eliminato. `OrderController` non scala più lo stock a mano (lo fa `OrderItem`).
Nell'admin il salvataggio viene bloccato con un avviso se servirebbero più pezzi
di quelli in magazzino (`ChecksOrderStock`). Nota: gli ordini annullati **prima**
di questa modifica non avevano restituito lo stock; il sistema non lo ricalcola a
ritroso.

Altre novità:
- **Indirizzo di spedizione salvato nell'ordine** (bug: prima non veniva salvato
  affatto, l'admin non sapeva dove spedire). È una copia (`shipping_address_line`,
  `shipping_postal_code`, `shipping_city`, `shipping_province`), modificabile
  dall'admin; gli ordini precedenti restano senza indirizzo
- **Tracking**: campi `carrier` (con suggerimenti), `tracking_number`,
  `tracking_url` nell'ordine admin; il cliente li vede nella pagina dell'ordine
  ("La tua spedizione") e li riceve via email
- **Link di tracking automatico** (aggiornato il 2026-09-29): con corriere "Poste
  Italiane" o "SDA" e link vuoto, il link si costruisce dal numero:
  `https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni/{codice}`
  (`Order::CARRIER_TRACKING_URLS`, esposto come `effective_tracking_url`), che apre
  direttamente la spedizione. Lo usano il pulsante "Segui la spedizione" dell'email e
  la pagina ordine; il cliente non deve più incollare il codice (il pulsante "Copia"
  resta). Un link inserito a mano dall'admin ha la precedenza; il form admin non
  compila più il campo "Link tracking" da solo. Gli ordini che avevano salvato la
  vecchia pagina generica sono stati corretti da una migration. Test:
  `backend/tests/Feature/TrackingUrlTest.php`
- **Corriere predefinito**: nel form ordine dell'admin è già selezionato "Poste
  Italiane" (`Order::DEFAULT_CARRIER`), sia nei nuovi ordini sia in quelli arrivati
  dal sito senza corriere; un corriere già scelto non viene toccato. Test:
  `backend/tests/Feature/OrderFormCarrierTest.php`
- **Riepilogo su WhatsApp** nella pagina ordine: "Invia su WhatsApp" apre la chat
  con il messaggio già scritto (prodotti, subtotale, sconto, spedizione, totale,
  nome, indirizzo, telefono, email). Testo in `src/utils/orderSummary.js`
- La pagina ordine mostra ora anche subtotale, importo dello sconto e indirizzo
- `items.*.product_id` deve essere `distinct` nella richiesta d'ordine (prima due
  righe dello stesso prodotto aggiravano il controllo di disponibilità)

Testato: logica di stock con creazione/modifica/annullo/riattivazione/cambio
prodotto/eliminazione in una transazione annullata (tutti i valori attesi); ordine
di prova reale via API (indirizzo salvato, totale corretto, stock scalato una sola
volta, email admin e cliente in coda, pagina ordine con tracking e messaggio
WhatsApp), poi eliminato con stock restituito.

## Messaggi di errore in italiano (nuovo, sessione del 2026-09-28)
Prima il backend non aveva i file di lingua italiani: i clienti vedevano chiavi
come `validation.unique` o `validation.required`. Ora ci sono:
- `backend/lang/it/validation.php`: tutti i messaggi di Laravel tradotti, con
  messaggi dedicati (`custom`) per username non valido o già in uso, email già
  registrata, password che non coincidono, CAP di 5 cifre, carrello vuoto, e i nomi
  leggibili dei campi (`attributes`: `address.phone` → "telefono", ecc.). Un
  campo nuovo in una validazione va aggiunto in `attributes`
- `lang/it/auth.php`, `passwords.php`, `pagination.php` e `lang/it.json` (il
  suffisso "(e altri N errori)" del messaggio riassuntivo)
- `lang/en` è la copia originale di Laravel pubblicata con `lang:publish`, fa da
  riferimento per le chiavi
- I messaggi scritti direttamente nei controller restano quelli (hanno la precedenza)

Testato via API: campi mancanti, username con spazi/@, email non valida, password
corta e diversa dalla conferma, CAP corto, username ed email già registrati: tutti
in italiano.

## Voce "Utenti" nell'admin (nuovo, sessione del 2026-09-28)
Pagina `/admin/users` (menu "Utenti", dopo "Ordini"), in sola lettura: niente
creazione, modifica o eliminazione dal pannello. Lo staff (`is_admin`) non compare.

**Statistiche in cima alla pagina** (widget in
`app/Filament/Resources/Users/Widgets/`, non in `app/Filament/Widgets`: quella
cartella finisce in automatico nella Dashboard):
- `UsersOverview`: utenti registrati (+ nuovi in 30 giorni), email verificate,
  accessi negli ultimi 30 giorni e utenti diversi, quanti hanno ordinato, quanti
  ricevono le offerte via email
- `UserActivityChart`: grafico accessi e nuove registrazioni al giorno (7/30/90 giorni)
- `TopCitiesChart`: le 10 città con più utenti (dall'indirizzo principale)
- `CookieBannerStats`: banner cookie mostrato / accettato / rifiutato / ignorato
  negli ultimi 30 giorni. Resta a zero finché Google Analytics non è attivo, perché
  fino ad allora il banner non compare

**Elenco** in ordine alfabetico: nome e username, email (copiabile), città e
provincia, email verificata, offerte via email, numero di ordini, totale speso (ordini
non annullati), accessi, ultimo accesso, data di registrazione. Ricerca per nome,
username ed email; filtri per email verificata, consenso offerte, ha/non ha ordinato,
città.

**Scheda utente** (`ViewUser`): profilo, attività, privacy e consensi (con le date),
indirizzi e ordini (con link all'ordine). Pulsante "Segna email come verificata" per
chi non riceve l'email di verifica e chiede aiuto.

**Nuovi dati raccolti per le statistiche:**
- Accessi: `users.last_login_at`, `users.login_count` e tabella `user_logins`
  (solo utente e data/ora, **niente IP**), scritti da `UserLogin::record()` a ogni
  login riuscito dal sito (non dal pannello admin). Lo storico si cancella dopo 12
  mesi (pulizia fatta ogni tanto durante i login, senza cron)
- Banner cookie: tabella `cookie_consent_stats` con i soli totali del giorno,
  alimentata da `POST /api/cookie-consent-stats` (`event`: `shown` /
  `accepted` / `rejected`, massimo 20 al minuto per IP) chiamata da
  `CookieBanner.jsx`. Nessun dato che identifica il visitatore
- Privacy e cookie policy aggiornate (storico accessi 12 mesi, statistiche interne
  per legittimo interesse, conteggi anonimi del banner)

Nota: nella traduzione italiana di Filament manca il testo "Sì/No" delle icone
nelle schede (`IconEntry`): nelle schede si usa un badge "Sì/No" al posto
dell'icona.

Test: `backend/tests/Feature/UserStatsTest.php` (accessi registrati solo se il
login riesce, conteggi del banner, elenco in ordine alfabetico senza lo staff,
scheda utente, caricamento di tutti i widget). Controllata anche dal vivo nel
pannello con dati di prova, poi cancellati.

## Cambio password ed eliminazione account (nuovo, sessione del 2026-09-28)
Decisione presa con l'utente: dal profilo si può cambiare **solo la password**;
nome, username ed email non sono modificabili dal sito. In fondo alla pagina
account c'è "Elimina account".

- `PUT /api/user/password` (`AccountController::updatePassword`): password
  attuale (`current_password:sanctum`), nuova password (min 8, confermata, diversa
  dall'attuale). Chiude gli accessi sugli altri dispositivi, non quello in uso.
  Frontend: `ChangePasswordCard.jsx`
- `DELETE /api/user` (`AccountController::destroy`): serve la password. Cancella
  account, indirizzi, carrello, preferiti, storico accessi (a cascata nel database),
  token e richieste di reset. **Gli ordini restano** (obblighi fiscali, 10 anni) con
  `user_id` a null: nome, email e indirizzo copiati nell'ordine restano visibili
  all'admin. Gli account dello staff non si eliminano dal sito. Entrambe le rotte:
  massimo 6 richieste al minuto
- Frontend: `DeleteAccountCard.jsx` (password + doppio click con `ConfirmButton`,
  che ora accetta `disabled`). Dopo l'eliminazione la pagina `/login?deleted=1` si
  ricarica da zero: con un cambio di pagina normale React Router arriva dopo la
  chiusura della sessione, e la pagina protetta rimandava al login senza messaggio
- Test: `backend/tests/Feature/AccountTest.php`. Provato anche dal vivo (password
  attuale sbagliata, cambio riuscito, eliminazione con password sbagliata e giusta)

## Ordini creati dall'admin (nuovo, sessione del 2026-09-29)
Dal pannello (`/admin/orders/create`) si creano ordini anche per clienti **ospiti**
(senza account), ad esempio per chi ordina su WhatsApp.

- **Disponibilità in tempo reale** nelle righe dell'ordine: accanto al nome del
  prodotto c'è "N disponibili" (gli esauriti non si possono scegliere); sotto la
  quantità compare "Disponibili: N", in rosso se si supera. Conta tutte le righe dello
  stesso prodotto e, in modifica, i pezzi che l'ordine tiene già riservati. Il
  salvataggio viene bloccato con l'errore sul campo; `ChecksOrderStock` resta come
  controllo finale lato server (`OrderForm::availabilityCheck()`)
- **Account registrato**: scegliendolo, nome, email, telefono e indirizzo principale
  si compilano da soli. Lo staff non compare nell'elenco
- **Email di conferma**: interruttore "Invia email di conferma al cliente" (attivo di
  default, solo in creazione, non salvato nell'ordine). Parte `OrderConfirmation`
  verso l'email del form, dopo il salvataggio delle righe. L'email mostra ora lo
  stato vero dell'ordine (prima diceva sempre "In attesa di pagamento"), fotografato
  al momento come per le email di cambio stato. Nessuna email all'indirizzo admin
  (l'ordine l'ha creato l'admin)
- **Invia su WhatsApp**: nella notifica "Ordine creato" e in alto nella pagina di
  modifica di ogni ordine. Apre WhatsApp nella chat del cliente con il riepilogo già
  scritto (prodotti, subtotale, sconto, spedizione, totale, indirizzo, stato e link
  di tracking se c'è), modificabile prima dell'invio. Testo e numero in
  `AppSupportOrderWhatsApp`: i numeri italiani senza prefisso ricevono il 39.
  Senza telefono valido il pulsante è grigio con il motivo nel suggerimento. Con un
  numero di tracking il messaggio ha il blocco "Tracciamento" (corriere, numero, link
  diretto). Usa i dati **salvati**: dopo aver aggiunto il tracking va salvato l'ordine
- Il vecchio link generico di Poste (senza codice) viene scartato e svuotato a ogni
  salvataggio (`Order::GENERIC_TRACKING_PAGE`): capitava con pagine del form aperte
  prima della modifica del 2026-09-29 (successo all'ordine #19, corretto)

**Ospiti e clienti registrati** (aggiunto il 2026-09-29):
- Un ordine da ospite non può usare l'email di un cliente registrato: il form lo
  segnala sotto il campo appena si esce dall'email e blocca il salvataggio, chiedendo
  di selezionare il cliente in "Account registrato" (controllo senza distinguere
  maiuscole/minuscole; gli account dello staff non contano)
- Se un ospite si registra dopo con la stessa email, i suoi ordini da ospite passano
  al suo account **quando verifica l'email** (link di verifica o pulsante "Segna email
  come verificata" dell'admin) e li vede nel profilo. Non alla registrazione:
  altrimenti chiunque potrebbe registrarsi con l'email di un altro e vederne ordini,
  indirizzo e telefono. Logica in `User::booted()` / `claimGuestOrders()`, senza
  email. Vale anche per chi elimina l'account e si registra di nuovo con la stessa
  email: ritrova i suoi vecchi ordini. Test: `backend/tests/Feature/GuestOrderLinkTest.php`

Test: `backend/tests/Feature/AdminCreateOrderTest.php`. Provato anche dal vivo nel
pannello (ordine da ospite, "Disponibili solo 10" con 99 pezzi, email in Mailpit,
notifica e link WhatsApp), poi cancellato.

## Conferma prima di inviare l'ordine (nuovo, sessione del 2026-10-07)
Richiesta dell'utente: senza pagamento online, un click per errore su "Conferma
ordine" crea un ordine che poi va annullato. Ora il pulsante nel checkout **non invia
più l'ordine**: apre una finestra di conferma (`OrderConfirmDialog.jsx`) che spiega
che cosa succede:
- l'ordine arriva subito a noi e i prodotti vengono riservati
- le istruzioni di pagamento arrivano su WhatsApp (sul sito non si paga)
- l'ordine è confermato solo quando arriva il pagamento

Pulsanti "Torna indietro" (anche Esc o click fuori, bloccati durante l'invio) e
"Sì, invia l'ordine", che fa partire `POST /api/orders` (`placeOrder()` in
`Checkout.jsx`). Se l'API rifiuta l'ordine la finestra si chiude e l'errore compare
nel riepilogo, come prima. Nessuna modifica al backend.

## Cosa manca ancora (prossimi passi)
- Istruzioni di pagamento: **non vanno sul sito**, le manda l'admin su WhatsApp
  (decisione presa con l'utente il 2026-09-28)
- Far rileggere privacy e cookie policy (fatte il 2026-09-28, vedi
  [PRIVACY.md](PRIVACY.md))
- Condizioni di vendita (recesso 14 giorni, resi, spedizioni)
- Verificare che i profili TikTok linkati nel footer (`@flbeauty`, `@flbeauty2`)
  siano esattamente quelli giusti (`src/config/contacts.js`)
