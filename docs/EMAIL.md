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
- Segmentazione destinatari nelle Comunicazioni (es. solo chi ha ordinato di recente)
- Tracking apertura/click sulle comunicazioni broadcast
- Template email multipli riutilizzabili invece del singolo editor libero
