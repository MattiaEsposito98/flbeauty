# Messa online su Aruba — procedura

Ultimo aggiornamento: 2026-10-07

Piano: **Hosting Linux Advanced** (vedi [HOSTING.md](HOSTING.md)). Tutto il sito sta su
**un solo indirizzo**, `https://www.flbeauty.it`:
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

## Dati del server (verificati via SSH il 2026-10-07)
- Connessione SSH (solo con chiave, vedi sotto): host `p5pws5i.zonep5.webhostingaruba.it`,
  porta **2222**, utente `k29tzap-flbeauty`
- Appena entri sei in `/web/htdocs/www.flbeauty.it/home`: **quella è la cartella
  pubblica del dominio** (contiene `cgi-bin`, `index.php`, `ver.php` segnaposto di
  Aruba). La cartella sopra è del sistema e non è scrivibile
- PHP da riga di comando: **8.3.23**; `composer` in `/usr/local/bin/composer`; `git` in
  `/usr/bin/git`
- Database MySQL 8.0 creato da Aruba (`Sql1964275_1`); host, utente e password sono nel
  pannello Aruba → Database (non vanno nel repository)
- Backup automatici già presenti (cartelle `…_Backup_Giornaliero` e `…_Settimanale`,
  visibili nel File Manager)

## Come sono organizzati i file sul server
Siccome si può scrivere solo dentro la cartella pubblica, il backend sta in una
sottocartella **chiusa al web** da regole nel `.htaccess` (e da un secondo `.htaccess`
dentro `backend/`). Nessuno può aprire `flbeauty.it/backend/.env`.

```
home/                            ← cartella pubblica di flbeauty.it
├── index.html, assets/…         ← negozio React (contenuto di frontend/dist)
├── .htaccess                    ← https, backend chiuso, /api e /admin al backend
├── _gateway/index.php           ← ingresso del backend
├── repo/                        ← git clone del progetto (chiuso al web)
├── backend/ → repo/backend      ← collegamento (chiuso al web)
├── css, js, fonts, images       ← collegamenti a backend/public/… (stile del pannello)
├── storage                      ← collegamento alle foto dei prodotti
└── sitemap.xml                  ← creata ogni notte dal backend
```

**La regola da non togliere mai:** in `.htaccess` la riga
`RewriteRule ^(backend|repo|_repo|vendor|storage/logs)(/|$) - [F,L]` è ciò che impedisce
di scaricare `.env`. Dopo ogni messa online controlla che
`https://www.flbeauty.it/backend/.env` dia **403 o 404** (vedi collaudo).

## 0. Collegarsi via SSH
Una volta sola (già fatto): chiave creata sul PC (`ssh-keygen -t rsa -b 4096 -f
~/.ssh/aruba_flbeauty`), chiave pubblica **senza commento** importata in Hosting Linux →
Strumenti e impostazioni → Chiavi SSH, utente creato nel tab "Utenti" (Aruba aggiunge il
prefisso `k29tzap-`). L'attivazione può richiedere qualche minuto. Poi, da PowerShell:
```powershell
ssh -i "$env:USERPROFILE\.ssh\aruba_flbeauty" -p 2222 k29tzap-flbeauty@p5pws5i.zonep5.webhostingaruba.it
```
Chiede la passphrase della chiave. La chiave **privata** non va mai condivisa. Non si può
clonare con `git@github.com:` (Aruba non permette git via SSH): si usa `https://`.

## 1. SSL e HTTPS
Menu **Sicurezza → Certificato SSL** per `flbeauty.it` (poi **Redirect HTTPS** se
previsto). Il `.htaccess` del sito rimanda comunque tutto su `https://www.flbeauty.it`.

## 2. Caricare il backend (via SSH, in `home`)
> **Ordine importante:** finché il `.htaccess` di dist/ (passo 3) non è caricato, `repo/` e
> `backend/` sarebbero apribili dal web. Quindi: clone e `composer install` ora, poi il
> passo 3 (carica dist con `.htaccess`) e il collaudo del blocco, e **solo dopo** crea il
> file `.env` (la riga `cp .env.production.example .env` e quelle seguenti).
```bash
git clone https://github.com/MattiaEsposito98/flbeauty.git repo
ln -s repo/backend backend
cd backend
composer install --no-dev --optimize-autoloader
cp .env.production.example .env      # SOLO dopo aver caricato il .htaccess (passo 3) e verificato il blocco
php artisan key:generate
php artisan migrate --force
php artisan filament:assets
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
(se il repository è privato, `git clone` chiede le credenziali: in alternativa carica i
file con il File Manager di Aruba.)

Compila il `.env` (modello: `backend/.env.production.example`, si modifica con
`nano .env`): `APP_ENV=production`, `APP_DEBUG=false`, database di Aruba, `APP_URL` e
`FRONTEND_URL` = `https://www.flbeauty.it`, `SITEMAP_PATH=/web/htdocs/www.flbeauty.it/home/sitemap.xml`,
SMTP.

Poi:
- **Comuni italiani**: `php artisan db:seed --class=ComuniSeeder` (7894 comuni, serve
  alla registrazione e alla spedizione per regione)
- **Tariffe di spedizione**: `php artisan db:seed --class=ShippingRateSeeder` (poi
  controlla prezzi e regioni dal pannello admin)
- **NON** lanciare `DemoDataSeeder`/`db:seed` completo: contiene dati finti
- **Il tuo utente admin**: `php artisan admin:create` (chiede nome, username, email e la password in modo nascosto, min. 12 caratteri; l'email risulta già verificata). Vecchio metodo con tinker:
  forte, `is_admin = 1`) e verifica l'email (`email_verified_at`)
- Permessi di scrittura su `storage/` e `bootstrap/cache/`

Per aggiornare in futuro: `cd repo && git pull`, poi in `backend`:
`composer install --no-dev -o`, `php artisan migrate --force`, e di nuovo i tre `*:cache`.

## 3. Costruire e caricare il negozio
Sul tuo PC:
```bash
cd frontend
cp .env.production.example .env.production   # VITE_API_URL=https://www.flbeauty.it/api
npm install
npm run build
```
Carica **tutto il contenuto di `dist/`** (compresi i file nascosti `.htaccess` e la
cartella `_gateway`) dentro `home`, sostituendo `index.php` e `ver.php` di Aruba (lascia
`cgi-bin`). **Non cancellare** `backend`, `repo`, `sitemap.xml` né i collegamenti del
passo 4: carica i file *sopra* quelli esistenti, senza svuotare la cartella.

## 4. Collegare le risorse del backend alla cartella pubblica
Il pannello admin ha bisogno di css, script, immagini e delle foto dei prodotti
(`/storage`), che stanno nel backend. Via SSH, in `home`:
```bash
bash repo/tools/aruba-link-public.sh .
```
Crea i collegamenti `css`, `js`, `fonts`, `images`, `storage` (se l'hosting non permette
i collegamenti copia i file: in quel caso va rilanciato dopo ogni aggiornamento del
backend). Controllo: `https://www.flbeauty.it/images/logo-mark.png` deve mostrare il logo.

## 5. Cron (pannello Aruba → Hosting Linux → Processi Cron)
Serve **un solo** cron, ogni minuto:
```bash
php /web/htdocs/www.flbeauty.it/home/backend/artisan schedule:run
```
Da lì Laravel lancia da solo (vedi `backend/routes/console.php`):
- ogni minuto l'invio delle email in coda (conferme ordine, verifica account,
  comunicazioni), che con la coda vuota fa una sola query e termina
- ogni notte alle 04:00 la sitemap per Google

**Carico**: trascurabile (equivale a una pagina aperta al minuto). Senza questo cron le
email non partono. Se il pannello non permette un cron al minuto (verificare), usa
l'intervallo minimo consentito, ad esempio 5 minuti: basta che sia `schedule:run`; le
email arriveranno con quel ritardo. Se il PHP del cron non è l'8.3, serve il percorso
completo del PHP 8.3 (si vede dalla guida Aruba o dal pannello).

## 6. Email
Vedi [EMAIL.md](EMAIL.md). Si parte con l'SMTP di Aruba (casella `info@flbeauty.it`),
Brevo si aggiunge più avanti per le comunicazioni di massa.

## 7. Controlli finali (checklist di collaudo)
- [ ] `https://www.flbeauty.it` si apre; `http://` e `flbeauty.it` (senza www) rimandano a `https://www.flbeauty.it` senza giri infiniti di redirect
- [ ] Aprendo direttamente `https://www.flbeauty.it/prodotti/<slug>` il prodotto si vede
- [ ] `https://www.flbeauty.it/admin` mostra il login del pannello, con stile e logo
- [ ] `https://www.flbeauty.it/api/categories` risponde con i dati
- [ ] **Sicurezza:** `https://www.flbeauty.it/backend/.env`, `/repo/.git/config`,
      `/backend/composer.json` e `/.env` devono dare **403 o 404**, mai il contenuto.
      Se uno si apre, togli subito `.env` e avvisami
- [ ] Le foto caricate dall'admin si vedono nel negozio (`/storage/...`)
- [ ] Registrazione di prova → email di verifica arriva → login funziona
- [ ] Ordine di prova → email di conferma al cliente e all'admin → tracking
- [ ] Comunicazione di prova dall'admin a un solo cliente
- [ ] `https://www.flbeauty.it/sitemap.xml` e `/robots.txt` rispondono
- [ ] Cookie banner: spento finché `VITE_GA_MEASUREMENT_ID` è vuoto
- [ ] Cancella l'utente e gli ordini di prova dal database
- [ ] Scarica una copia del database prima di aprire al pubblico (i backup automatici di
      Aruba ci sono già)

## Cose da verificare sul server (non testabili in locale)
Il file `_gateway` è stato provato in locale (avvia il backend e risponde alle API), ma
alcune cose dipendono da Aruba e si vedono solo online:
- che il server permetta i **collegamenti simbolici** (altrimenti lo script copia i file)
- che `.htaccess` con `mod_rewrite` sia attivo (di solito sì su hosting Linux Apache)
- che l'intestazione `Authorization` arrivi al backend (regola già nel `.htaccess`;
  si vede se il login dei clienti funziona)
- che la regola di blocco del backend funzioni (collaudo di sicurezza qui sopra)

## Indirizzo ufficiale: `https://www.flbeauty.it` (con www)
Aruba rimanda da solo `flbeauty.it` a `www.flbeauty.it` (a livello del suo server, prima
dei nostri file). Il `.htaccess` del sito forzava l'opposto e i due redirect si sarebbero
rimandati a vicenda all'infinito (scoperto il 2026-10-07 prima della messa online).
Quindi l'indirizzo ufficiale è **con www**: sitemap, canonical, dati strutturati, email e
`.env` usano tutti `https://www.flbeauty.it`. Non forzare mai la versione senza www.

## Aggiornare il sito dopo una modifica (flusso di lavoro)
Si lavora **in locale** (`.env` di sviluppo, XAMPP, `npm run dev`), si prova, poi si pubblica.
Il server ha impostazioni sue; non si copiano mai da un posto all'altro:

| Cosa | Sul PC (sviluppo) | Sul server (produzione) | Come si aggiorna |
|---|---|---|---|
| `backend/.env` | database XAMPP, `APP_DEBUG=true`, email su Mailpit | database Aruba, `APP_ENV=production`, SMTP reale | si modifica a mano sul server; **non è su GitHub** |
| `frontend/.env` / `.env.production` | API su `127.0.0.1:8000` | build con `VITE_API_URL=https://www.flbeauty.it/api` | `npm run build` legge `.env.production` |
| Dati (utenti, ordini, prodotti) | database locale | database Aruba | **separati**: nulla si trasferisce da solo |
| Foto prodotto (`storage/`) | sul PC | sul server | si caricano dall'admin online |
| `vendor/`, `sitemap.xml`, collegamenti `css/js/images/storage` | — | creati sul server | non vanno toccati |

**Backend** (codice PHP, migration, pannello): `git push`, poi sul server
`bash repo/tools/aruba-update-backend.sh` (scarica, aggiorna le librerie, esegue le
migration, rifà le cache e la sitemap). Serve l'accesso SSH. Caricare file PHP a mano
**non basta**: le cache di Laravel tengono la versione vecchia e le migration non
partono.

**Negozio** (React): `npm run build` sul PC e si carica il contenuto di `frontend/dist`
nella cartella pubblica (tasto destro → Upload da VS Code, o `scp`). Non cancellare
`backend`, `repo`, `sitemap.xml` e i collegamenti. Se la home sembra vecchia: Aruba →
Velocità → Caching → svuota cache.
