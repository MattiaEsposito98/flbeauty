# FLBeauty — istruzioni per Claude (leggi prima di lavorare)

Negozio online di prodotti beauty: **backend Laravel 12 + pannello Filament 5** (`backend/`) e **negozio React/Vite** (`frontend/`).
Online su **https://www.flbeauty.it** (hosting Aruba). Il titolare parla italiano: rispondi in italiano, in modo semplice e
concreto, senza dare per scontato che conosca i termini tecnici. Le guide del progetto sono in `docs/` (tienile aggiornate
a ogni modifica; indice in `README.md`). Il tema grafico del sito (rosa cipria / rose-gold) è fisso: vedi `docs/DESIGN.md`.

## Regole d'oro
- **Nessun segreto in git**: password, chiavi SSH, `.env`, passphrase non vanno mai in file del repository, nei commit o
  nelle risposte. Se l'utente incolla una password in chat, non riscriverla e consigliagli di cambiarla.
- **Le password si inseriscono sul server con un comando che non le mostra** (`read -rsp`), mai passando da te: vedi sotto.
- Prima di dire «fatto» verifica davvero (test, build, chiamate al sito online). Dì chiaramente cosa NON hai potuto provare.
- Dopo ogni modifica al `.env` del server: `php artisan config:cache`, e l'ultima riga del file deve finire con un «a capo».
- Lavora con commit piccoli e messaggi chiari (in coda: `Co-Authored-By: Claude …` come richiesto dall'ambiente).

## Deploy (metterlo online) — un solo comando
Il deploy si fa **dal PC dell'utente** con la chiave SSH già configurata. Se l'utente dice «fai il deploy», «metti online»:

1. Verifica i test: `cd backend && php artisan test --compact` (su Windows: `D:/xampp/php/php.exe artisan test --compact`) e la
   build: `cd frontend && npm run build`.
2. **Committa e fai push su `main`** (il backend si aggiorna scaricando da GitHub).
3. Lancia, dalla radice del progetto in Git Bash:
   ```bash
   bash tools/deploy.sh            # backend + negozio
   bash tools/deploy.sh backend    # solo backend (PHP, migration, pannello, email, API)
   bash tools/deploy.sh frontend   # solo negozio (React)
   ```
   Lo script controlla che tutto sia committato e pubblicato, aggiorna il backend sul server (`git pull`, `composer install`,
   `migrate --force`, cache, sitemap), costruisce e carica il negozio, e infine verifica il sito online (home, API, pannello e
   che `/backend/.env` e `/repo/.git` restino chiusi: devono dare 403/404).
4. Riferisci all'utente cosa è stato pubblicato e l'esito dei controlli. Se la home sembra vecchia: Aruba → Velocità → Caching →
   svuota cache (la fa l'utente dal pannello Aruba).

**Quando serve cosa:** modifiche a `backend/` → `backend`; modifiche a `frontend/src` (grafica, testi, contatti, SEO) → `frontend`;
prodotti, prezzi, categorie, foto, sconti, comunicazioni → **non serve alcun deploy** (si gestiscono dal pannello `/admin`).
Le migration partono da sole con il deploy del backend.

## Accesso al server (SSH)
| | |
|---|---|
| Host | `p5pws5i.zonep5.webhostingaruba.it` |
| Porta | `2222` |
| Utente | `k29tzap-claude` |
| Chiave privata | **`~/.ssh/aruba_claude`** (sul PC, **fuori dal repository**, senza passphrase) |
| Chiave pubblica | importata nel pannello Aruba (Hosting Linux → Strumenti e impostazioni → Chiavi SSH) |

Prova di connessione: `ssh -o BatchMode=yes -i ~/.ssh/aruba_claude -p 2222 k29tzap-claude@p5pws5i.zonep5.webhostingaruba.it "pwd"`
(deve rispondere `/web/htdocs/www.flbeauty.it/home`). Sul server ci sono PHP 8.3, `composer` e `git`; non si può clonare con
`git@github.com:` (Aruba non permette git via SSH): si usa `https://`. La chiave **non va mai** letta, stampata o copiata in
chat, file o commit. Per revocare l'accesso basta eliminare l'utente `claude` (e la sua chiave) dal pannello Aruba.

### Nuovo PC o chiave persa
Genera una nuova chiave senza commento e senza passphrase (`ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/aruba_claude`, poi
`cut -d' ' -f1,2 ~/.ssh/aruba_claude.pub` per togliere il commento), chiedi all'utente di importarla in Aruba (Chiavi SSH →
Importa chiave, **con un nome diverso da quelle esistenti**) e di associarla a un utente SSH; l'attivazione può richiedere
alcuni minuti. Quella dell'utente personale (con passphrase) è un'altra chiave e non va toccata.

## Com'è fatto il server
La cartella in cui si entra via SSH (`home`) è la **cartella pubblica del dominio**:
```
home/
├── index.html, assets/…      negozio React (contenuto di frontend/dist)
├── .htaccess                 https+www, backend chiuso, /api e /admin al backend — NON modificarlo a mano (viene da dist)
├── _gateway/index.php        ingresso del backend
├── repo/                     git clone del progetto (chiuso al web)
├── backend/ → repo/backend   collegamento (chiuso al web); qui c'è il .env di produzione (NON in git)
├── css, js, fonts, images, storage   collegamenti alle risorse del pannello e alle foto dei prodotti
└── sitemap.xml               generata ogni notte
```
Indirizzo ufficiale **con `www`** (Aruba rimanda già l'apex a www: non forzare il contrario). API: `/api`, pannello: `/admin`.
Dettagli e motivazioni: `docs/DEPLOY.md`, `docs/HOSTING.md`.

## File che esistono solo sul PC (non in git)
- `frontend/.env.production`: `VITE_API_URL=https://www.flbeauty.it/api`, `VITE_SITE_URL=https://www.flbeauty.it` e l'ID di Google
  Analytics (`VITE_GA_MEASUREMENT_ID=G-J0X2Z02YCC`). Senza questo file la build esce senza Analytics e con le API sbagliate
  (modello: `frontend/.env.production.example`).
- `backend/.env` locale (sviluppo, XAMPP): diverso da quello del server. Il `.env` del server sta in `backend/.env` sul server.

## Comandi utili sul server
Sempre via SSH nella cartella `home` (usa `ssh … "comando"`; per script lunghi scrivili in un file e passali con `bash -s < file`):
- Log email (una riga per email: INVIATA / ERRORE + motivo): `tail -n 30 backend/storage/logs/mail-$(date +%F).log`
- Errori generali: `tail -n 40 backend/storage/logs/laravel-$(date +%F).log`
- Cache dopo modifiche al `.env`: `cd backend && php artisan config:cache`
- Aggiornare solo il backend: `bash repo/tools/aruba-update-backend.sh` (è quello che usa `deploy.sh`)
- Creare un amministratore: `cd backend && php artisan admin:create` (chiede la password in modo nascosto)
- **Cambiare una password del `.env`** (es. database o casella email): l'utente la digita lui, tu non la vedi:
  `cd backend && read -rsp "Password: " P && echo && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=\"$P\"|" .env && unset P && php artisan config:cache`

## Trappole già incontrate (non ripeterle)
- **Windows / Git Bash**: gli heredoc con apostrofi (`'`) nel testo rompono la shell. Per scrivere file o script usa lo
  strumento di scrittura file (Write), non `cat <<EOF` con testo italiano. `sed` con `#` o apostrofi nel testo: meglio un piccolo
  script PHP o l'editor di file.
- **Test locali**: PHP di XAMPP (`D:/xampp/php/php.exe`); il database MySQL locale può essere spento, i test usano SQLite in memoria.
- **Livewire** (pannello) usa indirizzi con un codice (`/livewire-xxxx/update`): il `.htaccess` li manda al backend; se il pannello
  «non risponde ai clic» controlla che `/livewire-<codice>/livewire.min.js` sia JavaScript e non HTML.
- **Posta**: Aruba rifiuta l'invio a nome di un alias (`no-reply@` non funziona): tutte le email partono da `info@flbeauty.it`.
  Dalla riga di comando SSH le porte SMTP risultano chiuse, ma dal sito (web) l'invio funziona: per provare la posta usa una
  registrazione di prova sul sito e poi cancella l'utente di prova. Le email singole usano `QUEUE_CONNECTION=deferred`.
- **Cron di Aruba**: tipo PHP, file `backend/cron.php`, ogni 10 minuti (minimo consentito).
- **`.gitignore`**: `frontend/.env.production`, `.vscode/sftp.json` e i `.env` non vanno in git.
- Il carrello, gli ordini e il magazzino sono la parte più delicata (scorte per prodotto **e per variante**): dopo ogni modifica
  rilancia i test in `backend/tests/Feature` (`ProductVariantsTest`, `AdminOrderVariantsTest`, ecc.).

## Documentazione
`README.md` (indice) · `docs/DEPLOY.md` (messa online e struttura server) · `docs/TODO-ONLINE.md` (cose da fare/in sospeso) ·
`docs/FRONTEND.md` (negozio e funzioni, varianti dei prodotti) · `docs/EMAIL.md` · `docs/SECURITY.md` · `docs/PRIVACY.md` ·
`docs/SEO.md` · `docs/DESIGN.md`.
