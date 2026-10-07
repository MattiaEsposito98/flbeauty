# Messa online su Aruba — procedura

Ultimo aggiornamento: 2026-10-07

Piano: **Hosting Linux Advanced** (vedi [HOSTING.md](HOSTING.md)). Dominio:
`flbeauty.it` (negozio React) e `admin.flbeauty.it` (backend Laravel + pannello `/admin`
+ API). La lista di controllo con tutto ciò che resta da fare è in
[TODO-ONLINE.md](TODO-ONLINE.md).

> Questa guida è scritta prima dell'attivazione dell'hosting: i nomi esatti dei menu del
> pannello Aruba e i percorsi delle cartelle vanno verificati quando il servizio sarà
> attivo. Dove c'è `<...>` va inserito il valore reale.

## 0. Prima di iniziare
- Hosting "Attivo" nell'area clienti Aruba, email di Aruba con i dati di accesso
- PHP 8.2 o 8.3 selezionato per il dominio (Laravel 12 / Filament 5 richiedono ≥ 8.2)
- Database MySQL creato dal pannello: annota host, nome, utente e password
- Accesso SSH attivo (serve per `composer` e `artisan`)
- Sul tuo PC: il codice aggiornato da GitHub (`git pull`) e `npm`, `composer` installati

## 1. Sottodominio del backend
1. Nel pannello Aruba crea `admin.flbeauty.it` con la sua cartella
2. **Il document root deve puntare a `backend/public`**, non a `backend`. Se punta più
   in alto, il file `.env` (password del database) diventa scaricabile da chiunque.
   Se Aruba non permette di scegliere la sottocartella, carica il contenuto di
   `backend/` fuori dalla cartella pubblica e solo `backend/public` dentro (in quel caso
   va sistemato il percorso in `public/index.php`)
3. Attiva l'SSL (Let's Encrypt) per `flbeauty.it` e `admin.flbeauty.it`

## 2. Caricare il backend
Via SSH, nella cartella scelta:
```bash
git clone https://github.com/MattiaEsposito98/flbeauty.git
cd flbeauty/backend
composer install --no-dev --optimize-autoloader
cp .env.production.example .env      # poi compilalo (vedi sotto)
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Compila il `.env` (modello: `backend/.env.production.example`): `APP_ENV=production`,
`APP_DEBUG=false`, database di Aruba, `APP_URL`/`FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`,
`SITEMAP_PATH`, SMTP di Brevo.

Poi:
- **Comuni italiani**: `php artisan db:seed --class=ComuniSeeder` (7894 comuni, serve
  alla registrazione e alla spedizione per regione)
- **Tariffe di spedizione**: `php artisan db:seed --class=ShippingRateSeeder` (poi
  controlla prezzi e regioni dal pannello admin)
- **NON** lanciare `DemoDataSeeder`/`db:seed` completo: contiene dati finti
- **Il tuo utente admin**: crealo con `php artisan tinker` (nome, email, password
  forte, `is_admin = 1`) e verifica l'email (`email_verified_at`)
- Permessi di scrittura su `storage/` e `bootstrap/cache/`

Per aggiornare in futuro: `git pull`, `composer install --no-dev -o`,
`php artisan migrate --force`, poi di nuovo i tre `*:cache` (oppure
`php artisan optimize:clear` e rifarli).

## 3. Cron (pannello Aruba)
Serve **un solo** cron, ogni minuto:
```bash
php <percorso>/backend/artisan schedule:run
```
Da lì Laravel lancia da solo (vedi `backend/routes/console.php`):
- ogni minuto l'invio delle email in coda (conferme ordine, verifica account,
  comunicazioni), che con la coda vuota fa una sola query e termina
- ogni notte alle 04:00 la sitemap per Google

**Carico**: trascurabile (equivale a una pagina aperta al minuto). Senza questo cron le
email non partono. Se il pannello Aruba non permette un cron al minuto (verificare), usa
l'intervallo minimo consentito, ad esempio 5 minuti: basta che sia `schedule:run`; le
email arriveranno con quel ritardo. Verifica con un ordine di prova.

## 4. Il negozio React
Sul tuo PC:
```bash
cd frontend
cp .env.production.example .env.production   # compila (VITE_API_URL ecc.)
npm install
npm run build
```
Carica **tutto il contenuto di `dist/`** (compreso il file nascosto `.htaccess`) nella
cartella pubblica di `flbeauty.it`, **senza cancellare `sitemap.xml`** se già presente.
Il `.htaccess` fa tre cose: porta tutto su `https://flbeauty.it` (senza www), rimanda gli
indirizzi interni a `index.html` (altrimenti `/prodotti/...` dà 404) e imposta cache e
intestazioni di sicurezza.

## 5. Email reali (Brevo)
Vedi [EMAIL.md](EMAIL.md): account Brevo, verifica del dominio con i record SPF/DKIM
nel DNS di Aruba, poi le variabili `MAIL_*` nel `.env`.

## 6. Controlli finali (checklist di collaudo)
- [ ] `https://flbeauty.it` si apre, `http://` e `www.` rimandano a `https://flbeauty.it`
- [ ] Aprendo direttamente `https://flbeauty.it/prodotti/<slug>` il prodotto si vede
- [ ] `https://admin.flbeauty.it/admin` mostra il login; `.env` NON è scaricabile
      (`https://admin.flbeauty.it/.env` deve dare 403/404)
- [ ] Le foto caricate dall'admin si vedono nel negozio
- [ ] Registrazione di prova → email di verifica arriva → login funziona
- [ ] Ordine di prova → email di conferma al cliente e all'admin → tracking
- [ ] Comunicazione di prova dall'admin a un solo cliente
- [ ] `https://flbeauty.it/sitemap.xml` e `/robots.txt` rispondono
- [ ] Cookie banner: spento finché `VITE_GA_MEASUREMENT_ID` è vuoto
- [ ] Cancella l'utente e gli ordini di prova dal database
- [ ] Backup: attiva i backup automatici di Aruba e scarica una copia del database
      prima di aprire al pubblico
