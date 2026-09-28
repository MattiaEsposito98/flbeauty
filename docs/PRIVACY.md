# Privacy, cookie e consenso marketing

Ultimo aggiornamento: 2026-09-28

## Decisioni prese con l'utente
- **Privacy policy** e **cookie policy** pubblicate sul sito (`/privacy`, `/cookie`),
  collegate nel footer, nella registrazione e nell'account
- **Titolare**: Flavia Esposito, persona fisica (nessuna ragione sociale, sede o
  P. IVA al momento). Contatto: l'email del negozio. Testi generici sul fornitore di
  hosting (server nell'UE), senza nomi
- **Età minima 14 anni**, dichiarata nella stessa casella della privacy
  ("Dichiaro di aver compiuto 14 anni e di aver letto l'informativa privacy"):
  nessuna data di nascita richiesta. Se si scopre un account di un minore di 14
  anni, va cancellato (lo dice l'informativa, paragrafo 8)
- **Registrazione**: casella obbligatoria privacy + età +
  casella **facoltativa e non preselezionata** per le email promozionali
- **Profilo**: il cliente può attivare o disattivare le email promozionali in ogni
  momento (card "Privacy e comunicazioni" in `Account.jsx`)
- **Comunicazioni dall'admin**: due tipi, vedi sotto
- **Banner cookie**: pronto ma spento finché non si attiva Google Analytics

## Perché il consenso ai cookie non è nella registrazione
Il consenso ai cookie non tecnici deve essere libero e separato: non si può legare
alla registrazione né renderlo obbligatorio (linee guida del Garante, 2021). Serve un
banner a ogni primo accesso al sito, con "Accetta" e "Rifiuta" dello stesso peso e
la possibilità di cambiare idea in ogni momento. Nella registrazione resta solo
l'informativa privacy (presa visione) e il consenso marketing.

Oggi il sito usa solo strumenti **tecnici** (memoria locale del browser: `token` di
accesso e `cart` del carrello ospite), che non richiedono consenso. Per questo il
banner non compare finché non c'è Google Analytics.

## Comunicazioni: promozionali o di servizio
| Tipo | Destinatari | Contenuto ammesso |
|---|---|---|
| **Promozionale** | Solo clienti registrati con `marketing_consent = true` | Offerte, sconti, nuovi prodotti. Ogni email ha il link "Disiscriviti" |
| **Di servizio** | Tutti i clienti registrati + le email lasciate sugli ordini | Solo avvisi su account e ordini: modifica delle condizioni, ritardi nelle spedizioni, chiusura per ferie, problemi di sicurezza. **Mai offerte**: inviarle senza consenso è vietato (art. 130 Codice Privacy), anche se "importanti" |

- `Communication::recipients($type)` calcola i destinatari; il tipo si sceglie nel
  form admin (Radio con il conteggio dei destinatari per tipo) e compare come badge
  nello storico. Le comunicazioni inviate prima di questa modifica sono state
  segnate come promozionali
- **Gli utenti registrati prima di oggi non hanno il consenso** (`false`): non
  riceveranno più le promozionali finché non lo attivano dal profilo. Le email
  lasciate sugli ordini non ricevono mai le promozionali
- Il link a piè di email e la differenza di testo sono in
  `resources/views/emails/broadcast.blade.php` (slot `footer` del layout mail)

## Disiscrizione dalle email promozionali
- Link firmato senza scadenza: `MarketingConsentController::unsubscribeUrls($user)`
  restituisce `api` (rotta `POST /api/unsubscribe/{user}`, middleware
  `signed:relative`) e `page` (`{FRONTEND_URL}/disiscrizione?u=…&signature=…`)
- La pagina del sito (`Unsubscribe.jsx`) chiede conferma e poi chiama la rotta
  API. **Niente disiscrizione su GET**: i filtri antispam aprono i link delle email
  in automatico e disiscriverebbero il cliente da soli
- Header `List-Unsubscribe` + `List-Unsubscribe-Post: List-Unsubscribe=One-Click`:
  Gmail e Outlook mostrano il pulsante "Annulla iscrizione" (dal 2024 Gmail lo
  richiede a chi invia email in massa)
- Firma falsa o modificata → 403

## Dati salvati (tabella `users`)
- `privacy_accepted_at`: quando ha dichiarato di aver letto l'informativa (vuoto
  per chi si è registrato prima)
- `marketing_consent` + `marketing_consent_at`: consenso attuale e data dell'ultima
  modifica, come prova del consenso. Si cambia solo con
  `User::setMarketingConsent()` (non è mass assignable)

- `last_login_at`, `login_count` e tabella `user_logins`: storico accessi senza
  IP, conservato 12 mesi, per le statistiche della voce "Utenti" dell'admin
- Tabella `cookie_consent_stats`: solo totali giornalieri anonimi del banner

## API
- `POST /api/register`: `privacy_accepted` obbligatorio (`accepted`),
  `marketing_consent` facoltativo
- `PATCH /api/user/marketing-consent` (con login): `{ marketing_consent: bool }`,
  restituisce l'utente aggiornato
- `POST /api/unsubscribe/{user}?signature=…`: disiscrizione senza login

## Banner cookie e Google Analytics (per quando si va online)
1. Creare la proprietà GA4 e copiare l'ID (`G-XXXXXXXXXX`)
2. Nel `.env` del frontend di produzione: `VITE_GA_MEASUREMENT_ID=G-XXXXXXXXXX`, poi
   ricompilare (`npm run build`)
3. Compare il banner (`CookieBanner.jsx`); Analytics si carica **solo dopo
   "Accetta"**, e in footer compare "Preferenze cookie" per cambiare scelta. Se il
   cliente rifiuta dopo aver accettato, Analytics viene disattivato e i cookie
   `_ga` cancellati
4. La cookie policy mostra in automatico la sezione Google Analytics e la voce
   `cookie_consent`; scelta salvata per 6 mesi, poi viene richiesta
5. Nelle impostazioni di GA4 conviene ridurre la conservazione dei dati a 2 mesi

Logica in `src/utils/cookieConsent.js`. Le pagine viste vengono inviate a ogni
cambio di pagina (sito a pagina singola, `send_page_view: false` + evento manuale).

## Da completare prima di andare online
- Se in futuro ci saranno ragione sociale, sede o P. IVA, aggiungerle in
  `frontend/src/config/legal.js` e nel paragrafo 1 di `Privacy.jsx`
- **Far rileggere i testi** di privacy e cookie policy a un consulente: sono una
  base completa, non un parere legale
- Nel `.env` di produzione `LOG_STACK=daily` (già in `.env.example`): i log
  contengono gli IP e l'informativa dichiara una conservazione di 14 giorni
- Ancora da fare: **condizioni di vendita** (diritto di recesso 14 giorni, resi,
  spedizioni). L'eliminazione dell'account dal profilo c'è (vedi FRONTEND.md)

## Test
- Automatici: `backend/tests/Feature/MarketingConsentTest.php`: destinatari per
  tipo, invio dal pannello (link di disiscrizione solo nelle promozionali),
  disiscrizione con firma valida e falsa, modifica dal profilo. `php artisan test`
- Per far girare i test su SQLite, la migration dell'indice full-text dei prodotti
  ora salta SQLite (su MySQL non cambia nulla)
- Dal vivo (2026-09-28): registrazione senza privacy rifiutata, con/senza marketing
  salvata correttamente; promozionale arrivata su Mailpit con link e header di
  disiscrizione; disiscrizione dal link completata dal sito; interruttore nel
  profilo; banner con ID di prova: niente Analytics prima del consenso, "Rifiuta"
  salvato, riapertura dal footer, "Accetta" carica Analytics; banner controllato
  anche a 375 px
