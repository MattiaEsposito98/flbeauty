# Frontend — stato e decisioni

Ultimo aggiornamento: 2026-09-26

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

## Cosa manca ancora (prossimi passi)
- Pagina di modifica dati account (nome/email/password)
