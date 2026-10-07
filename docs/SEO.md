# SEO — come il sito si fa trovare su Google

Ultimo aggiornamento: 2026-10-07

Dominio definitivo: **https://www.flbeauty.it** (negozio React; pannello e API sullo stesso dominio, `/admin` e `/api`)
(backend, pannello e API). Il negozio è una SPA React: l'HTML che arriva è quasi
vuoto e i contenuti compaiono dopo il caricamento. Google esegue il JavaScript, ma
con più ritardi di un sito classico. Per questo ogni pagina dichiara da sola titolo,
descrizione, indirizzo canonico e dati strutturati (vedi sotto).

## Cosa è stato fatto (2026-10-07)

### Titolo, descrizione e anteprime per pagina — `src/components/Seo.jsx`
Componente che scrive nell'`<head>`: `<title>`, `meta description`, `robots`, link
`canonical`, Open Graph (`og:*`) e Twitter card. Alla chiusura della pagina i valori
tornano a quelli di default (`index.html`). Valori di base in `src/config/site.js`
(dominio da `VITE_SITE_URL`, di default `https://www.flbeauty.it`).

| Pagina | Titolo | Indicizzata |
|---|---|---|
| Home (`/`) | F&L Beauty \| Prodotti beauty scelti con cura | sì |
| Categoria (`/categoria/{slug}`) | `{Categoria} \| F&L Beauty`, descrizione dalla categoria in admin | sì |
| Prodotto (`/prodotti/{slug}`) | `{Nome} \| F&L Beauty`, descrizione = primi ~155 caratteri, foto = prima immagine | sì |
| Pagina 2+ del catalogo | `… – pagina N`, canonical con `?page=N` | sì |
| Privacy, cookie | titolo dedicato | sì |
| Ricerche (`?q=`) | "Risultati per …" | **noindex** |
| Login, registrazione, password, carrello, account, preferiti, checkout, ordini, disiscrizione | titolo dedicato | **noindex** |
| Indirizzo inesistente | "Pagina non trovata" | **noindex** |

- Le pagine `noindex` restano raggiungibili dal sito, ma Google non le mostra.
  Titoli/noindex delle pagine "statiche" sono nella tabella `STATIC_SEO` in `App.jsx`.
- **Descrizione delle categorie**: la scrivi in admin (campo "Descrizione" della
  categoria); se è vuota il sito ne usa una generica. Conviene scriverne una vera
  (1-2 frasi con le parole che le clienti cercano, es. "rossetti matte e lucidi").
- **Descrizione dei prodotti**: è il testo che Google mostra sotto il titolo. Meglio
  frasi complete e uniche per prodotto, non copiate dal fornitore.

### Categorie come pagine vere — `/categoria/{slug}`
Prima la categoria era un filtro (`/?category=…`), che per Google è la stessa pagina.
Ora ogni categoria ha il suo indirizzo, titolo e descrizione. Il vecchio formato
`/?category=…` rimanda in automatico al nuovo. Nelle pagine categoria e ricerca il
titolo `h1` è il nome della categoria; sulla home resta "Il tuo momento di bellezza".

### Dati strutturati (JSON-LD, schema.org)
- In tutte le pagine (`index.html`): `Organization` (nome, logo, email, telefono,
  TikTok) e `WebSite`
- Pagina prodotto: `Product` con prezzo in euro, disponibilità (`InStock` /
  `OutOfStock`), foto e categoria, più `BreadcrumbList` (Catalogo › Categoria › Prodotto)
- Servono a mostrare prezzo e disponibilità direttamente nei risultati. Per controllare:
  https://search.google.com/test/rich-results

### Sitemap — `sitemap.xml`
- Elenca home, categorie con almeno un prodotto attivo, prodotti attivi (con foto e
  data di modifica) e privacy/cookie, con gli indirizzi di `flbeauty.it`
- Codice in `backend/app/Support/Sitemap.php`; comando `php artisan sitemap:generate`
  (scrive il file) e rotta `/sitemap.xml` del backend (per controllarla da browser)
- **Aggiornamento automatico**: il comando è schedulato **ogni notte alle 04:00**
  (`routes/console.php`), quindi un prodotto nuovo o disattivato compare/sparisce in
  sitemap entro un giorno. Richiede il cron di Laravel, vedi sotto
- **Percorso del file**: la sitemap deve stare nella cartella pubblica del sito, dove sta il
  negozio (`www.flbeauty.it`): in produzione imposta `SITEMAP_PATH` nel `.env` del backend
  a `<spazio web>/www.flbeauty.it/sitemap.xml`
- Il caricamento del sito React (`dist/`) non deve cancellare quel file: copia
  `dist/` **senza svuotare** la cartella, oppure rigenera subito la sitemap dopo ogni
  deploy

### robots.txt
- **Un solo file** (`frontend/public/robots.txt`), perché negozio, pannello e API sono sullo
  stesso dominio. Blocca `/admin`, `/api`, `/livewire` e `/_gateway`, più `/ordini/`,
  `/reimposta-password`, `/disiscrizione` (indirizzi con token personali) e le ricerche
  interne `?q=`; indica la sitemap. Lascia aperto `/storage` (foto dei prodotti). Le altre
  pagine private **non** sono bloccate di proposito: Google deve poter aprire la pagina
  per leggere il suo `noindex`
- `backend/public/robots.txt` vale solo in sviluppo o se il backend è raggiunto da solo

### Foto prodotto più leggere
Le foto caricate dall'admin (galleria e "Scatta una foto") vengono ridotte a massimo
**1600 px** prima del caricamento, senza ingrandirle. Una foto da telefono da 5 MB
diventa di poche centinaia di KB: pagine più veloci (conta per Google) e meno traffico.

## Cron da attivare in produzione (Aruba)
Un solo cron, ogni minuto (lo stesso che spedisce le email, vedi [DEPLOY.md](DEPLOY.md)):
```bash
php /percorso/backend/artisan schedule:run
```
Lancia la sitemap ogni notte. Senza questo cron la sitemap non si aggiorna da sola
(si può sempre lanciare a mano `php artisan sitemap:generate`).

## Dopo la messa online
1. **Google Search Console** (https://search.google.com/search-console): aggiungi la
   proprietà `flbeauty.it`, verifica il dominio (record DNS TXT da Aruba) e invia
   `https://www.flbeauty.it/sitemap.xml`. Poi "Controllo URL" sulla home per chiedere
   l'indicizzazione
2. Controlla la velocità con PageSpeed Insights (https://pagespeed.web.dev/)
3. Se vendi anche localmente, crea la scheda **Google Business Profile**
4. Il canonical e il redirect: `http://` e `flbeauty.it` (senza www) devono rimandare a
   `https://www.flbeauty.it` (301) dal pannello Aruba/`.htaccess`, così Google vede un solo
   indirizzo
5. Le anteprime sui social: oggi la foto di anteprima è il logo (`logo-mark-360.webp`,
   360 px). Per un risultato migliore su WhatsApp/Instagram prepara un'immagine
   **1200×630** con logo e slogan e cambia `DEFAULT_IMAGE` in `src/config/site.js`

## Limiti noti e prossimi passi
- **Anteprima social dei singoli prodotti**: WhatsApp, Facebook e simili non eseguono
  il JavaScript, quindi se incolli il link di un prodotto vedono i dati generici
  (logo, titolo del sito), non la foto del prodotto. Google invece li legge. Per
  risolverlo serve il *prerendering* delle pagine prodotto (HTML già pronto lato
  server): intervento più grosso, da valutare dopo i primi mesi online
- Tempo di indicizzazione: per una SPA Google può impiegare giorni o settimane per le
  pagine nuove. La sitemap e Search Console lo accelerano
- Mancano pagine di contenuto ("Chi siamo", spedizioni e resi, condizioni di vendita):
  aiutano fiducia e posizionamento. Le condizioni di vendita sono comunque da scrivere
- `/catalogo` fa ancora un redirect lato JavaScript: in produzione conviene un 301 vero
  nel `.htaccess`
- Il file `.htaccess` del negozio deve rimandare tutti gli indirizzi a `index.html`
  (altrimenti `/prodotti/...` dà 404 aprendolo direttamente), vedi [HOSTING.md](HOSTING.md)

## Test
`backend/tests/Feature/SitemapTest.php` (contenuto della sitemap, comando, noindex del
backend). Il resto è stato provato a mano nel browser: categoria, prodotto, ricerca e
pagina inesistente (titolo, description, canonical, robots, JSON-LD).
