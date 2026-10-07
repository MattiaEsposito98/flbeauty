# Email — stato del progetto

Ultimo aggiornamento: 2026-09-26

## Cosa esiste oggi

### 1. Email automatiche sul cambio stato ordine
Quando lo stato di un ordine cambia (Nuovo → In lavorazione → Evaso / Annullato) dal
pannello admin, il cliente riceve automaticamente una email di aggiornamento.

- Mailable: [`backend/app/Mail/OrderStatusUpdated.php`](../backend/app/Mail/OrderStatusUpdated.php)
- Vista: [`backend/resources/views/emails/order-status.blade.php`](../backend/resources/views/emails/order-status.blade.php)
- Trigger: [`backend/app/Observers/OrderObserver.php`](../backend/app/Observers/OrderObserver.php),
  registrato in `AppServiceProvider::boot()`
- Parte solo se l'ordine ha un `customer_email` e lo stato è effettivamente cambiato.

### 2. Comunicazioni broadcast (offerte/novità)
> **Aggiornato il 2026-09-28**: ora ci sono due tipi, *promozionale* (solo a chi ha
> dato il consenso marketing, con link di disiscrizione) e *di servizio* (a tutti,
> solo avvisi su account e ordini). Dettagli e regole in [PRIVACY.md](PRIVACY.md).

> **Aggiornato il 2026-10-07**: per le comunicazioni *di servizio* c'è l'opzione "A chi
> inviarla": **Tutti i clienti** oppure **Solo i clienti che scelgo io** (selezione
> multipla con ricerca per nome, username o email, solo clienti registrati, staff
> escluso). Serve per avvisi mirati (es. un problema con un ordine). La selezione
> manuale **non esiste per le promozionali**, che restano limitate a chi ha dato il
> consenso (`Communication::recipients($type, $userIds)` la ignora). Lo storico mostra
> la colonna "Inviata a" (`communications.audience`: `all` / `selected`). Test in
> `backend/tests/Feature/MarketingConsentTest.php`.

Nuova voce di menu **Comunicazioni** nel pannello admin (`/admin/communications`).
Permette di scrivere un oggetto + testo (editor ricco) e inviarlo via email a tutti i
clienti: sia quelli registrati sia i clienti "guest" che hanno lasciato una email su un
ordine. L'invio parte subito dopo il salvataggio e resta uno storico consultabile
(oggetto, numero destinatari, chi l'ha inviata, quando).

- Modello: [`backend/app/Models/Communication.php`](../backend/app/Models/Communication.php)
  — il metodo `recipientEmails()` calcola la lista destinatari (deduplicata)
- Mailable: [`backend/app/Mail/BroadcastCommunication.php`](../backend/app/Mail/BroadcastCommunication.php)
- Resource Filament: [`backend/app/Filament/Resources/Communications/`](../backend/app/Filament/Resources/Communications/)
- Ogni destinatario riceve una email singola (nessun indirizzo email è visibile agli
  altri destinatari).

> **Dal 2026-09-28** passano dalla coda anche l'email di **verifica** e quella di
> **reimposta password** (`AppNotificationsQueuedVerifyEmail` e
> `QueuedResetPassword`, usate da `User::sendEmailVerificationNotification()` e
> `sendPasswordResetNotification()`). Prima partivano subito: se il server email non
> rispondeva, la registrazione andava in errore anche se l'account era già stato
> creato. Ora la registrazione riesce sempre e l'email viene inviata (con 3
> tentativi a distanza di un minuto) appena il worker è attivo. **Senza queue worker
> in produzione nessuna email parte**, nemmeno quella di verifica.

> **Email di cambio stato e coda (corretto il 2026-09-29)**: la coda salva solo un
> riferimento all'ordine e lo rilegge dal database quando l'email parte. Prima, se
> lo stato cambiava di nuovo prima dell'invio (es. worker spento, o "In lavorazione"
> e subito "Evaso"), tutte le email mostravano l'ultimo stato. Ora
> `OrderStatusUpdated` salva stato, corriere, numero e link di tracking nel momento
> del cambio. Test: `tests/Feature/OrderStatusEmailTest.php`. Nota per lo sviluppo:
> un `queue:work` sempre acceso tiene in memoria il codice vecchio, dopo una modifica
> va riavviato (`php artisan queue:restart`).

Entrambe le mailable implementano `ShouldQueue`: gli invii non bloccano il pannello,
finiscono in coda (tabella `jobs`) e vengono spediti dal **queue worker** (vedi sotto).

### 3. Email all'admin per ogni nuovo ordine (sessione del 2026-09-27)
A ogni ordine dal sito parte `App\Mail\NewOrderForAdmin` verso l'indirizzo in
`ADMIN_ORDER_EMAIL` (`.env`, oggi `Flbeauty32@gmail.com`): cliente, telefono,
indirizzo di spedizione, prodotti con quantità e prezzi, sconto, spedizione,
totale e pulsante "Apri l'ordine nel pannello". Il "Rispondi" dell'email va
direttamente al cliente. Se la variabile è vuota l'email non parte.

Riepilogo ordine condiviso tra email al cliente e all'admin:
`resources/views/emails/partials/order-summary.blade.php`.

L'email di cambio stato ora dice "In attesa di pagamento" per gli ordini nuovi e,
quando l'ordine è evaso con un numero di tracking, mostra corriere, numero e
"Segui la spedizione". Parte anche se il tracking viene inserito dopo aver già
messo l'ordine in "Evaso" (`OrderObserver`).

## Come si testa in locale (nessun account esterno, nessun dominio)

Le email non vengono davvero spedite su internet: vengono catturate da **Mailpit**, un
finto server SMTP che gira sul PC e mostra le email ricevute in una webmail locale.

**Modo più semplice**: `tools/start-dev.bat` (doppio click) avvia tutto insieme —
backend, sito React, Mailpit e worker delle code — e apre nel browser il sito e la
casella di Mailpit. Le email arrivano **solo su Mailpit** finché nel `.env` c'è
`MAIL_HOST=127.0.0.1` / `MAIL_PORT=1025`: per riceverle davvero serve un SMTP reale
(Brevo, vedi sotto).

In alternativa, solo la parte email:

1. Avvia `tools/start-dev-mail.bat` (doppio click) — apre due finestre:
   - **Mailpit**: cattura le email, interfaccia su http://127.0.0.1:8025
   - **Queue worker** (`php artisan queue:work`): spedisce davvero le email in coda
     verso Mailpit
2. Lascia il backend Laravel avviato come al solito (`php artisan serve`)
3. Fai un cambio di stato ordine o invia una comunicazione dal pannello admin
4. Apri http://127.0.0.1:8025 per vedere le email arrivate, con anteprima HTML

Configurazione già impostata in `backend/.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="no-reply@flbeauty.it"
```

Se chiudi le finestre di Mailpit/queue worker, le email restano "in coda" finché non
rilanci il worker — non vengono perse, semplicemente non partono finché nessuno le
processa.

## Percorso verso la produzione (quando ci sarà il dominio flbeauty.it)

Piano deciso con l'utente:

1. **Aruba** (o altro registrar): acquisto del dominio `flbeauty.it`. Da lì si possono
   anche attivare caselle email "vere" da leggere (es. info@flbeauty.it), se servono.
2. **Brevo**: provider scelto per l'invio automatico delle email applicative (conferme
   ordine, cambio stato, comunicazioni broadcast) — gestisce sia invii transazionali
   sia campagne marketing, piano gratuito disponibile, adatto al caso d'uso.
3. Collegamento: si crea un account Brevo, si verifica il dominio flbeauty.it
   aggiungendo i record DNS richiesti (SPF/DKIM) nel pannello Aruba.
4. Passaggio in produzione: basta aggiornare le variabili `MAIL_*` nel `.env` di
   produzione (mailer `smtp` o driver dedicato Brevo, host/credenziali forniti da
   Brevo) — il codice applicativo non cambia.

Nota: Brevo NON sostituisce una casella di posta leggibile (tipo Gmail/Outlook) — serve
solo a spedire. Le caselle "vere" restano su Aruba o Google Workspace/Zoho, se servono.

## Possibili estensioni future (non ancora fatte)
- Segmentazione automatica destinatari nelle Comunicazioni (es. solo chi ha ordinato di
  recente; la scelta manuale dei clienti c'è già per quelle di servizio)
- Tracking apertura/click sulle comunicazioni broadcast
- Template email multipli riutilizzabili invece del singolo editor libero

## Quando partono le email in produzione (2026-10-08)
- **Email singole** (verifica account, conferma ordine, cambio stato, reset password): partono
  **subito dopo la risposta al cliente** (`QUEUE_CONNECTION=deferred` nel `.env` di produzione).
  Non servono il cron né un worker. Se l'invio fallisce non viene riprovato: l'errore resta in
  `storage/logs` (volumi piccoli, accettato)
- **Comunicazioni dall'admin**: fino a **30 destinatari** partono subito; oltre, passano dalla coda
  `database` e le spedisce il cron di Aruba (`backend/cron.php`, ogni 10 minuti) a gruppi
  (`CreateCommunication::IMMEDIATE_LIMIT`)
- In sviluppo resta `QUEUE_CONNECTION=database` con `queue:work` e Mailpit, come prima
