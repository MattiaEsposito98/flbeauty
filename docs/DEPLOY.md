# Messa online su Aruba — procedura

Ultimo aggiornamento: 2026-10-07

Piano: **Hosting Linux Advanced** (vedi [HOSTING.md](HOSTING.md)). Tutto il sito sta su
**un solo indirizzo**, `https://flbeauty.it`:
- `flbeauty.it/` → negozio React
- `flbeauty.it/admin` → pannello di gestione (Filament) per l'amministratore
- `flbeauty.it/api` → API usate dal negozio

La lista di controllo con tutto ciò che resta da fare è in
[TODO-ONLINE.md](TODO-ONLINE.md).

## Perché non c'è un sottodominio `admin.flbeauty.it`
Su Aruba un sottodominio con cartella propria è un servizio a pagamento a parte ("terzo
livello", 15 €/anno; il nome `admin` è inoltre riservato) e "Redirect e sottodomini"
serve solo a inoltrare verso un altro indirizzo. Decisione presa con l'utente
(2026-10-07): usare un solo indirizzo, senza costi in più. In più cookie e CORS non
sono un problema, perché negozio e API sono sullo stesso dominio.

## Come sono organizzati i file sul server
Nello spazio web (File Manager / FTP / SSH) c'è già la cartella pubblica del dominio,
`www.flbeauty.it`, accanto alle cartelle dei backup. Il backend va **accanto**, non
dentro: così il file `.env` con le password non è raggiungibile dal web.

```
<spazio web>/
├── backend/                  ← il contenuto di backend/ del repository (fuori dal web)
│   ├── .env                  ← password e impostazioni di produzione
│   └── public/               ← cartella con css, js, immagini del pannello
├── www.flbeauty.it/          ← cartella pubblica = contenuto di frontend/dist
│   ├── index.html, assets/…  ← negozio React
│   ├── .htaccess             ← regole: https, /api e /admin al backend, resto al negozio
│   ├── _gateway/index.php    ← ingresso del backend (copiato dalla build)
│   ├── css, js, fonts, images, storage   ← collegamenti a backend/ (vedi §4)
│   └── sitemap.xml           ← creata ogni notte dal backend
└── flbeauty.it_Backup_…      ← backup automatici di Aruba
```

## 0. Prima di iniziare
- Hosting "Attivo", **PHP 8.3** impostato (Hosting Linux → Strumenti e impostazioni →
  Gestione PHP), **database MySQL 8.0** già creato da Aruba (`Sql1964275_1`: host,
  utente e password sono nel pannello Database)
- Accesso SSH: Strumenti e impostazioni → **Chiavi SSH** (si accede con una chiave: si
  genera sul PC e si importa lì; la guida è nel link "Consulta la nostra guida")
- **SSL**: Sicurezza → Certificato SSL, e Sicurezza → Redirect HTTPS
- Sul tuo PC: il codice aggiornato da GitHub (`git pull`), `npm` installato

## 1. SSL e HTTPS
1. Menu **Sicurezza → Certificato SSL**: attiva il certificato per `flbeauty.it`
2. Menu **Sicurezza → Redirect HTTPS** (se previsto dal piano; il `.htaccess` del sito
   comunque rimanda tutto su `https://flbeauty.it`)

## 2. Caricare il backend (fuori dalla cartella pubblica)
Via SSH, nella cartella che contiene `www.flbeauty.it`:
```bash
git clone https://github.com/MattiaEsposito98/flbeauty.git repo
mv repo/backend backend            # il backend va accanto a www.flbeauty.it
cd backend
composer install --no-dev --optimize-autoloader
cp .env.production.example .env    # poi compilalo (vedi sotto)
php artisan key:generate
php artisan migrate --force
php artisan filament:assets
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
(se `composer` non è disponibile sul server, si può lanciare `composer install` sul tuo
PC e caricare anche la cartella `vendor/`: più lento ma funziona.)

Compila il `.env` (modello: `backend/.env.production.example`): `APP_ENV=production`,
`APP_DEBUG=false`, database di Aruba, `APP_URL`/`FRONTEND_URL` = `https://flbeauty.it`,
`SITEMAP_PATH`, SMTP.

Poi:
- **Comuni italiani**: `php artisan db:seed --class=ComuniSeeder` (7894 comuni, serve
  alla registrazione e alla spedizione per regione)
- **Tariffe di spedizione**: `php artisan db:seed --class=ShippingRateSeeder` (poi
  controlla prezzi e regioni dal pannello admin)
- **NON** lanciare `DemoDataSeeder`/`db:seed` completo: contiene dati finti
- **Il tuo utente admin**: crealo con `php artisan tinker` (nome, email, password
  forte, `is_admin = 1`) e verifica l'email (`email_verified_at`)
- Permessi di scrittura su `storage/` e `bootstrap/cache/`

Per aggiornare in futuro: `git pull` (nella cartella `repo`) e copiare `backend/`
aggiornato, poi `composer install --no-dev -o`, `php artisan migrate --force`, e
di nuovo i tre `*:cache`.

## 3. Costruire e caricare il negozio
Sul tuo PC:
```bash
cd frontend
cp .env.production.example .env.production   # VITE_API_URL=https://flbeauty.it/api
npm install
npm run build
```
Carica **tutto il contenuto di `dist/`** (compresi i file nascosti `.htaccess` e la
cartella `_gateway`) nella cartella `www.flbeauty.it`, sostituendo i file segnaposto di
Aruba (`index.php`, `ver.php`; lascia `cgi-bin`). **Non cancellare** `sitemap.xml`, né i
collegamenti del passo 4, se già presenti.

## 4. Collegare le risorse del backend alla cartella pubblica
Il pannello admin ha bisogno di css, script, immagini e delle foto dei prodotti
(`/storage`), che stanno nel backend. Via SSH, nella cartella che contiene `backend` e
`www.flbeauty.it`:
```bash
bash repo/tools/aruba-link-public.sh .
```
Crea i collegamenti `css`, `js`, `fonts`, `images`, `storage` dentro `www.flbeauty.it`
(se l'hosting non permette i collegamenti, copia i file: in quel caso va rilanciato dopo
ogni aggiornamento del backend). Controllo: `https://flbeauty.it/images/logo-mark.png`
deve mostrare il logo.

## 5. Cron (pannello Aruba → Hosting Linux → Processi Cron)
Serve **un solo** cron, ogni minuto:
```bash
php <percorso>/backend/artisan schedule:run
```
Da lì Laravel lancia da solo (vedi `backend/routes/console.php`):
- ogni minuto l'invio delle email in coda (conferme ordine, verifica account,
  comunicazioni), che con la coda vuota fa una sola query e termina
- ogni notte alle 04:00 la sitemap per Google

**Carico**: trascurabile (equivale a una pagina aperta al minuto). Senza questo cron le
email non partono. Se il pannello non permette un cron al minuto (verificare), usa
l'intervallo minimo consentito, ad esempio 5 minuti: basta che sia `schedule:run`; le
email arriveranno con quel ritardo.

## 6. Email
Vedi [EMAIL.md](EMAIL.md). Si parte con l'SMTP di Aruba (casella `info@flbeauty.it`),
Brevo si aggiunge più avanti per le comunicazioni di massa.

## 7. Controlli finali (checklist di collaudo)
- [ ] `https://flbeauty.it` si apre, `http://` e `www.` rimandano a `https://flbeauty.it`
- [ ] Aprendo direttamente `https://flbeauty.it/prodotti/<slug>` il prodotto si vede
- [ ] `https://flbeauty.it/admin` mostra il login del pannello, con stile e logo
- [ ] `https://flbeauty.it/api/categories` risponde con i dati
- [ ] I file del backend NON sono raggiungibili: `https://flbeauty.it/backend/.env` e
      `https://flbeauty.it/.env` devono dare 404
- [ ] Le foto caricate dall'admin si vedono nel negozio (`/storage/...`)
- [ ] Registrazione di prova → email di verifica arriva → login funziona
- [ ] Ordine di prova → email di conferma al cliente e all'admin → tracking
- [ ] Comunicazione di prova dall'admin a un solo cliente
- [ ] `https://flbeauty.it/sitemap.xml` e `/robots.txt` rispondono
- [ ] Cookie banner: spento finché `VITE_GA_MEASUREMENT_ID` è vuoto
- [ ] Cancella l'utente e gli ordini di prova dal database
- [ ] I backup automatici di Aruba esistono già (cartelle `…_Backup_Giornaliero` e
      `…_Backup_Settimanale`); scarica comunque una copia del database prima del lancio

## Cose da verificare sul server (non testabili in locale)
Questa configurazione è stata provata in locale (il file `_gateway` avvia il backend
correttamente), ma alcune cose dipendono da Aruba e si vedono solo online:
- che il server permetta i **collegamenti simbolici** (altrimenti lo script copia i file)
- che `.htaccess` con `mod_rewrite` sia attivo (di solito sì su hosting Linux Apache)
- che l'intestazione `Authorization` arrivi al backend (regola già nel `.htaccess`;
  si vede se il login dei clienti funziona)
- la versione di `composer`/`php` da riga di comando (`php -v` via SSH deve essere 8.3;
  se è diversa, usare il percorso completo del PHP 8.3 indicato da Aruba)
