# Sicurezza — protezione account e dati clienti

Ultimo aggiornamento: 2026-09-28

Obiettivo (deciso con l'utente): impedire che qualcuno entri negli account dei
clienti indovinando le password, e bloccare i bot su registrazione e form pubblici.
Nessun servizio esterno (niente captcha di Google/Cloudflare, niente cookie di terze
parti): tutto gira sul nostro backend.

## Limite di tentativi

| Rotta | Limite | Dove |
|---|---|---|
| `POST /api/login` — stesso account dallo stesso IP | 5 password sbagliate → bloccato 15 minuti | `AuthController::login` (`LOGIN_LIMITS`) |
| `POST /api/login` — stesso account da IP qualsiasi | 20 password sbagliate → bloccato 1 ora (attacchi distribuiti) | `AuthController::login` |
| `POST /api/login` — per IP, su tutti gli account | 20 richieste al minuto | limiter `login` in `AppServiceProvider` |
| `POST /api/register` | 10 richieste all'ora per IP | limiter `register` in `AppServiceProvider` |
| forgot / reset password, reinvio verifica | 6 al minuto per IP (già esistente) | `routes/api.php` |

- Per il blocco per account contano solo i tentativi **falliti**. Un accesso riuscito
  azzera il contatore di quell'IP. L'account è riconosciuto senza distinguere
  maiuscole/minuscole, e il blocco vale sia che si scriva l'email sia lo username
- Durante il blocco anche la password giusta viene rifiutata (risposta 429), con il
  messaggio "Troppi tentativi. Riprova tra N minuti."
- Compromesso accettato: chi conosce l'email di un cliente può bloccargli l'accesso
  per al massimo un'ora sbagliando la password apposta. Il cliente può comunque
  usare "Password dimenticata?"
- I 429 delle API hanno un messaggio in italiano sotto la chiave `form`
  (`bootstrap/app.php`, testo in `App\Support\Throttle::message()`)
- I contatori stanno nella cache di Laravel (`CACHE_STORE`); `php artisan
  cache:clear` li azzera tutti, utile se serve sbloccare un cliente subito

## Blocco degli account (nuovo, 2026-10-07)
Per chi fa ordini fasulli (il sito è aperto e non c'è pagamento online). Dall'admin,
in **Utenti**: pulsante "Blocca account" nella scheda utente e icona nell'elenco
(più "Sblocca account"); l'elenco ha la colonna "Stato" e il filtro Account.

- **Blocca**: la finestra elenca gli **ordini aperti** (in attesa di pagamento o in
  lavorazione) e propone "Annulla anche gli ordini aperti" (attivo di default). Gli
  ordini vengono annullati uno per uno, quindi i pezzi tornano in magazzino da soli
  (`Order::booted()`) e il cliente riceve la normale email di cambio stato. Motivo
  facoltativo, visibile solo all'admin nella scheda utente
- **Effetti**: tutti gli accessi aperti vengono chiusi (token cancellati) e il login
  è rifiutato con "Il tuo account è sospeso…". Il messaggio compare solo dopo aver
  controllato la password, così chi non la conosce non scopre se l'account esiste
- **Sblocca**: riattiva l'account; gli ordini già annullati restano annullati
- Dati: `users.blocked_at` e `users.blocked_reason`; logica in `User::block()` /
  `unblock()`, azioni in `Filament/Resources/Users/Actions/BlockUserActions.php`
- Limite noto: il cliente bloccato può registrarsi di nuovo con un'altra email
  (la registrazione è aperta); il blocco ferma chi usa quell'account, non la persona
- Test: `backend/tests/Feature/BlockUserTest.php`

## Anti-bot (honeypot + tempo minimo)
Middleware `App\Http\Middleware\BlockBots`, alias `bot.guard`:
- **Honeypot**: i form hanno un campo `website` spostato fuori dallo schermo
  (classe `.bot-trap`, non `display:none`, così i bot lo considerano un campo
  normale). Se arriva compilato, la richiesta viene respinta
- **Tempo minimo** (`bot.guard:3`): il form invia `form_time`, cioè i millisecondi
  passati da quando è stato mostrato. Sotto i 3 secondi la richiesta è respinta
- Applicato a: registrazione (honeypot + 3 secondi), login e password dimenticata
  (solo honeypot: con il gestore password una persona può inviarli in un attimo)
- Il frontend usa l'hook `useBotTrap()` (`src/components/BotTrap.jsx`): `{trap}`
  va dentro il `<form>`, `botFields()` va aggiunto ai dati inviati
- Richiesta respinta → 422 con errore `form` ("Non siamo riusciti a inviare il
  modulo...") e una riga `warning` in `storage/logs/laravel.log` con IP e motivo
- Limite noto: ferma i bot generici, non un attacco scritto apposta per questo
  sito. Se in produzione arrivassero comunque registrazioni spam, il passo
  successivo è Cloudflare Turnstile (gratuito, serve un account Cloudflare)

## Sessioni (token Sanctum)
- I token di accesso scadono dopo **30 giorni** (`config/sanctum.php`,
  sovrascrivibile con `SANCTUM_EXPIRATION` in minuti), poi il cliente deve rifare
  il login
- **Reset password → tutti i token dell'utente vengono cancellati**: se qualcuno era
  entrato con la vecchia password, viene buttato fuori da ogni dispositivo

## Già presenti prima di questa sessione (verificati)
- Ordini e indirizzi controllano che appartengano all'utente loggato (403
  altrimenti); carrello e wishlist lavorano sempre su `$request->user()`
- "Password dimenticata" e "reinvia verifica" rispondono sempre in modo generico,
  senza rivelare se un'email è registrata

## Frontend
- `src/utils/apiError.js` → `apiError(err, campo, ripiego)`: restituisce l'errore
  del campo, altrimenti l'errore generale `form`, altrimenti il testo di ripiego.
  Usato in Login, Register, ForgotPassword, ResetPassword
- `ForgotPassword.jsx` prima non gestiva gli errori (in caso di 429 non succedeva
  niente); ora li mostra. Anche "Invia di nuovo" nel login gestisce l'errore

## Da fare prima di andare online
- **`APP_DEBUG=false`** e `APP_ENV=production` nel `.env` di produzione: con il
  debug attivo, in caso di errore l'API mostra il dettaglio dell'errore, i percorsi
  dei file e parte della configurazione
- HTTPS obbligatorio sul dominio (i token viaggiano nell'header `Authorization`)
- Se il sito sta dietro un proxy/CDN, configurare `trustProxies` in
  `bootstrap/app.php`, altrimenti tutti i clienti sembrano avere lo stesso IP e
  condividono i limiti per IP

## Test fatti (2026-09-28, dal vivo sul server locale)
Login con honeypot compilato → 422; registrazione in meno di 3 secondi → 422;
registrazione "umana" → arriva normalmente alla validazione; password dimenticata
con honeypot → 422 (anche dal browser, messaggio mostrato nel form); 5 password
sbagliate → la sesta, anche se giusta e scritta con maiuscole diverse, riceve 429
"Riprova tra 15 minuti"; 20 richieste al minuto dallo stesso IP → 429 "Riprova
tra 1 minuto"; reset password → token attivi da 2 a 0; accesso normale dal browser
riuscito. Utente di test rimosso a fine prova.
