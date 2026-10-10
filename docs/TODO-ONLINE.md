# Da fare per la messa online (in sospeso fino all'attivazione dell'hosting)

Ultimo aggiornamento: 2026-10-07. Spunta le voci man mano che le fai. La procedura
tecnica passo passo è in [DEPLOY.md](DEPLOY.md).

## 1. Aruba (appena l'hosting è "Attivo")
- [x] Hosting attivo (dominio attivo il 2026-10-07)
- [x] PHP 8.3 impostato (Strumenti e impostazioni → Gestione PHP)
- [x] Database MySQL 8.0 già creato da Aruba (`Sql1964275_1`, vuoto): annotare host, utente e password (non nel repository)
- [x] Accesso SSH attivo (chiave `aruba_flbeauty`, utente `k29tzap-flbeauty`, porta 2222): PHP 8.3.23, composer e git disponibili
- [x] ~~Sottodominio `admin.flbeauty.it`~~ — NON serve: si usa `flbeauty.it/admin` (su Aruba il terzo livello costa 15 €/anno, vedi DEPLOY.md)
- [ ] Attivare il certificato SSL per `flbeauty.it` (Sicurezza → Certificato SSL e Redirect HTTPS)
- [ ] Verificare nel pannello che il **rinnovo (79,99 € + IVA)** includa ancora le 5
      caselle PEC, e segnare la scadenza (7/10/2027, rinnovo automatico attivo)
- [ ] Chiedere all'assistenza quanti domini aggiuntivi supporta il piano (se si vuole un
      secondo progetto, vedi HOSTING.md)
- [ ] Creare la casella `info@flbeauty.it` (e, se serve, la PEC)
- [x] Backup automatici già presenti (cartelle `..._Backup_Giornaliero` e `_Settimanale`); [ ] scaricare comunque una copia del database prima del lancio

## 2. Caricamento del sito
- [ ] Backend: clone, `composer install`, `.env` di produzione, `migrate`, `storage:link`,
      `*:cache` (DEPLOY.md §2). Migration nuove dalla ultima volta: comunicazioni
      `audience`, utenti `blocked_at`/`blocked_reason`
- [ ] Seeder `ComuniSeeder` e `ShippingRateSeeder` (mai `DemoDataSeeder`)
- [ ] Creare il proprio utente admin con `is_admin = 1`
- [ ] Cron di Aruba (tipo PHP, file `backend/cron.php`, ogni 10 minuti): serve per le comunicazioni di massa e la sitemap
      (spedisce le email in coda e aggiorna la sitemap di notte; se Aruba non ammette
      un minuto, usare l'intervallo minimo, es. 5 minuti)
- [ ] `SITEMAP_PATH` nel `.env` e prima `php artisan sitemap:generate`
- [ ] `CORS_ALLOWED_ORIGINS=https://www.flbeauty.it` nel `.env`
- [ ] Frontend: `.env.production`, `npm run build`, caricare `dist/` (con `.htaccess`)
- [ ] Ricaricare le foto dei prodotti dall'admin (quelle locali non si trasferiscono;
      il "Lenzuolo bianco" è senza immagine), inserire i prodotti veri, controllare
      categorie, prezzi e **tariffe di spedizione** per regione
- [ ] Cancellare dati di prova (utenti, ordini, prodotti demo) prima del lancio

## 3. Email (Brevo)
- [ ] Creare l'account Brevo
- [ ] Verificare il dominio `flbeauty.it` con i record SPF/DKIM nel DNS di Aruba
- [ ] Mettere le credenziali SMTP nel `.env` di produzione
- [ ] Provare: registrazione, conferma ordine, cambio stato, comunicazione dall'admin
- [ ] Controllare che le email non finiscano in spam (provare Gmail e Outlook)

## 4. Google
- [x] Search Console: proprietà "Dominio" `flbeauty.it` verificata con record TXT (2026-10-08, account flbeauty32@gmail.com)
      `https://www.flbeauty.it/sitemap.xml`, chiedere l'indicizzazione della home
- [ ] Testare un prodotto con https://search.google.com/test/rich-results
- [ ] PageSpeed Insights sulla home e su un prodotto
- [ ] Google Business Profile (se si vende anche in zona)
- [x] Google Analytics 4 collegato (2026-10-08): ID `G-J0X2Z02YCC` in `frontend/.env.production` (file locale, non su GitHub), banner cookie attivo. Da fare in GA: conservazione dati a 2 mesi, Segnali Google disattivati
      la build (attiva il banner cookie). In GA4 ridurre la conservazione dati a 2 mesi
      (vedi PRIVACY.md)
- [ ] Preparare un'immagine di anteprima **1200×630** (logo + slogan) e impostarla in
      `frontend/src/config/site.js` (`DEFAULT_IMAGE`)

## 5. Testi legali e contenuti (da fare prima di vendere)
- [ ] **Verificare con il commercialista la posizione fiscale** per vendere online
      (partita IVA, apertura attività, obblighi del commercio elettronico). La privacy
      oggi indica come titolare Flavia Esposito come persona fisica, senza P. IVA né sede:
      se cambia, aggiornare `frontend/src/config/legal.js` e `Privacy.jsx`
- [ ] Scrivere le **condizioni di vendita**: recesso 14 giorni, resi, spedizioni,
      tempi, pagamento fuori dal sito (Postepay) — con un consulente
- [ ] Pagine "Chi siamo", "Spedizioni e resi", "Contatti" (aiutano fiducia e Google)
- [ ] Far rileggere privacy e cookie policy a un consulente
- [x] Profili TikTok nel footer corretti (`@fl.beauty`, `@fl_beauty2`, 2026-10-08) in
      `frontend/src/config/contacts.js`
- [ ] Scrivere le descrizioni delle **categorie** e dei **prodotti** pensando alle
      parole che le clienti cercano (compaiono su Google)
- [ ] Decidere come comunicare le istruzioni di pagamento su WhatsApp (testo standard)

## 6. Dopo il lancio
- [ ] Fare un ordine vero di prova end-to-end e una registrazione vera
- [ ] Controllare ogni settimana: ordini "In attesa di pagamento" vecchi da annullare,
      utenti sospetti da bloccare (Admin → Utenti)
- [ ] Valutare tra qualche mese il *prerendering* delle pagine prodotto (anteprime
      social con la foto del prodotto) — vedi SEO.md
- [ ] Segnare in calendario il rinnovo dell'hosting e del dominio

## Idee future (non urgenti)
- Recensioni e valutazioni dei prodotti (stelle su Google)
- Pagamento online (carta/PayPal) al posto di Postepay
- Notifiche WhatsApp automatiche invece che manuali
- Registrazione solo con conferma del numero di telefono, per frenare gli ordini
  fasulli (oggi: blocco account dall'admin)
- Conferma d'ordine con data stimata di consegna

## Stato della messa online (aggiornato 2026-10-07, sera)
- [x] Backend online su `https://www.flbeauty.it` (`/api`, `/admin`): migration eseguite,
      comuni (7894) e tariffe di spedizione (21) caricati, nessun dato di prova
- [x] Blocco del codice dal web verificato (`/backend/.env`, `/repo/.git` → 403)
- [x] Indirizzo ufficiale **con www** (Aruba rimanda già l'apex a www)
- [x] Utente admin creato (`info@flbeauty.it`)
- [x] Negozio React caricato (www.flbeauty.it)
      risponde 500 sulle pagine finché manca `index.html`
- [ ] **Cambiare la password del database** (è comparsa in chat) e rifare `read -s` nel `.env`
- [x] Cron di Aruba impostato (PHP, `backend/cron.php`, ogni 10 minuti): verificare la colonna "Ultima esecuzione"
- [x] Email: SMTP di Aruba con `info@flbeauty.it` attivo e provato (verifica account arrivata a Hotmail, 2026-10-08)
- [x] Utente SSH `claude` e chiave `~/.ssh/aruba_claude`: **si TENGONO** per i deploy automatici (decisione del 2026-10-10, vedi CLAUDE.md). Per revocare l'accesso: eliminare l'utente e la chiave in Aruba (Chiavi SSH)
- [ ] Cambiare la passphrase della chiave SSH personale (`ssh-keygen -p`), è comparsa in chat
