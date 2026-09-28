# Design system del sito (frontend)

Ultimo aggiornamento: 2026-09-27

Riferimento unico per l'aspetto del negozio React. **Ogni nuova pagina o modifica
grafica deve rispettare queste regole** (decisione presa con l'utente: il tema resta
questo anche per le aggiunte future).

## Direzione
- Boutique beauty **accogliente, femminile ed elegante**: il pubblico previsto è
  circa al 90% femminile
- Colori e forme vengono dal logo (`assets/brand/logo.png`): rosa cipria, rose-gold
  metallico, cornice a doppio ottagono
- Tono di voce caldo e inclusivo, con formule neutre invece del maschile/femminile
  ("Ti diamo il benvenuto", "Che bello rivederti!", "per prenderti cura di te")
- Tutto in italiano, prezzi nel formato italiano ("5,00 €")

## Palette (token in `frontend/src/index.css`, `:root`)
| Token | Hex | Uso |
|---|---|---|
| `--rose-50` | `#fdf8f7` | Superfici chiare, sfondo del carrello laterale |
| `--rose-100` | `#faeeec` | Sfondo pagina (con grana "carta" leggerissima) |
| `--rose-200` | `#f5e1e0` | Blush del logo: hover, cerchi icona, badge |
| `--rose-300` | `#ebcfcd` | Bordi di input, pillole, bottoni outline |
| `--rose-400` | `#d6a1a4` | Accenti tenui, icone placeholder |
| `--rose-500` | `#b76e79` | **Rose-gold del brand** (stesso primary dell'admin): icone, bordi attivi, focus |
| `--rose-600` | `#a35c68` | Fondo dei bottoni principali |
| `--rose-700` | `#8e4f5b` | Link, prezzi, testo accentato |
| `--rose-800` | `#6e3c47` | Hover scuri |
| `--taupe` | `#8d6c65` | Etichette maiuscole (`.eyebrow`), categorie |
| `--ink` / `--ink-soft` | `#3d2b2f` / `#7b6468` | Testo principale / secondario |
| `--success`, `--warning`, `--danger` | verde salvia, ambra, lampone | Stati (disponibile, in lavorazione, errori) |

**Regola di contrasto**: `--rose-500` con testo bianco non raggiunge il contrasto
minimo per testi normali, quindi bottoni e badge pieni usano `--grad-button`
(`#a85f6b → #8e4f5b`) e i testi colorati su bianco usano `--rose-700`. Il
`--rose-500` resta per icone, bordi, focus e titoli molto grandi.

Gradienti pronti: `--grad-rose-gold` (decorativo/metallico), `--grad-rose-gold-text`
(testo in rose-gold leggibile), `--grad-button`, `--grad-blush` (hero e card soft).

## Tipografia
- **Playfair Display** (serif ad alto contrasto, richiama "F&L" del logo): titoli
  `h1–h3`, prezzi, totali. In un titolo, la parola chiave va in `<em>`: diventa
  corsivo con gradiente rose-gold (es. "Il tuo momento di *bellezza*")
- **Jost** (geometrico, richiama "BEAUTY" del logo): testo, interfaccia, bottoni
- Etichette maiuscole molto spaziate con la classe `.eyebrow` (sopra i titoli)
- Font **self-hosted** via `@fontsource-variable/*` (import in `src/main.jsx`): niente
  richieste a Google Fonts, quindi più veloci e senza problemi GDPR

## Icone
- Libreria `react-icons`: set **Lucide** (`react-icons/lu`) per l'interfaccia,
  **Font Awesome 6** (`react-icons/fa6`) solo per i marchi (WhatsApp, TikTok)
- Icone decorative sempre con `aria-hidden="true"`; i bottoni solo-icona hanno un
  `aria-label` in italiano
- I nomi Lucide sono quelli recenti (`LuCircleCheck`, non `LuCheckCircle`;
  `LuHouse`, non `LuHome`): in caso di dubbio verificare in
  `node_modules/react-icons/lu/index.d.ts`

## Motivi grafici
- **Doppio ottagono** del logo: componente `OctagonOrnament` (hero, emblema delle
  pagine di accesso); avatar dell'account ritagliato a ottagono
- Card con angoli ampi (`--radius-lg`), ombre morbide rosate (`--shadow*`)
- Micro-animazioni leggere (ingresso pagina, cuore che "pulsa", carrello che scorre
  da destra), disattivate automaticamente con `prefers-reduced-motion`

## Componenti riutilizzabili (`frontend/src/components/`)
| Componente | Uso |
|---|---|
| `Logo` | Marchio + scritta, header e footer (`className="logo-light"` su sfondo scuro) |
| `Navbar` | Barra annuncio WhatsApp + header sticky con icone e badge |
| `Footer` | Footer con contatti, TikTok e link di navigazione |
| `WhatsAppButton` | Pulsante flottante "Scrivici" |
| `ProductCard` / `ProductImage` | Card prodotto; immagine con fallback elegante se manca o non si carica |
| `WishlistButton` | Cuoricino (tondo sulle card, `withLabel` nella pagina prodotto) |
| `QuantityStepper` | Selettore quantità − / + (`size="sm"` nel carrello laterale) |
| `CartDrawer` | Carrello laterale (si chiude con ×, click fuori o Esc) |
| `CartToast` | Avviso in basso: "aggiunto al carrello" (verde) o limite di disponibilità (`warning`, arancione) |
| `CartAdjustmentsNotice` | Riquadro con le quantità corrette automaticamente per disponibilità cambiata |
| `ConfirmButton` | Pulsante con conferma interna per azioni distruttive (niente `confirm()` del browser) |
| `PasswordField` | Campo password con lucchetto e occhiello mostra/nascondi |
| `AuthCard` | Card centrata delle pagine login/registrazione/password |
| `Alert` | Messaggi `success` / `error` / `info` con icona |
| `EmptyState` | Stati vuoti con icona, titolo, testo e azione |
| `Spinner` | Caricamenti |
| `OctagonOrnament` | Motivo decorativo del logo |
| `CookieBanner` | Banner consenso cookie (solo con Google Analytics configurato), "Accetta" e "Rifiuta" con lo stesso stile |

## Classi CSS di riferimento
- Bottoni: `.btn` + `.btn-primary` / `.btn-outline` / `.btn-ghost` /
  `.btn-ghost-danger`, taglie `.btn-sm` / `.btn-lg`, `.btn-block`; bottoni tondi
  solo-icona `.icon-btn` (con `.icon-badge` per i contatori)
- Struttura pagina: radice `.page` (animazione di ingresso), intestazione
  `.page-header` con `.eyebrow`, `h1` e `.page-subtitle`
- Contenitori: `.card`, `.card-header`, `.card-title` (con icona), `.icon-circle`
  (`.icon-circle-lg` per gli stati vuoti)
- Form: `.field`, `.form-grid` (2 colonne, `.span-2` a tutta larghezza),
  `.input-icon` (input con icona), `.checkbox`, `.form-actions`
- Testi: `.hint`, `.error`, `.muted`, `.text-link`, `.link-button`
- Filtri e stati: `.pill` (+ `.active`), `.badge`, `.status-badge .status-<stato>`,
  `.in-cart-badge`
- Pagine legali: `.legal-page`, `.legal-content` (testo lungo), `.legal-table`;
  caselle di consenso su più righe `.checkbox.consent-checkbox`
- Riepiloghi: `.summary-items`, `.summary-lines`, `.summary-row`, `.summary-total`

## Dati e formattazione
- Contatti (WhatsApp, email, TikTok) solo in `src/config/contacts.js`: header,
  footer, pulsante flottante e pagina prodotto leggono da lì
- Prezzi sempre con `formatPrice()`, numeri ordine con `formatOrderNumber()`,
  etichette stato con `ORDER_STATUS_LABELS` (`src/utils/format.js`)
- Logo per il web in `frontend/public/` (`logo-mark-128.webp` ~3 KB,
  `logo-mark-360.webp` ~12 KB, `favicon.png`, `apple-touch-icon.png`), ricavati da
  `assets/brand/logo-mark-512.png` (350 KB, troppo pesante per il sito)

## Responsive
Breakpoint: 1024 / 960 / 720 / 480 px. Su telefono: header solo icone, annuncio
accorciato, pillole categorie scorrevoli, griglia prodotti a 2 colonne, hero
compatto senza sottotitolo (i prodotti devono vedersi subito, visto che la home è
il catalogo), form a una colonna, pulsante WhatsApp solo icona.

## Checklist per le prossime modifiche
1. Colori solo tramite token (`var(--rose-…)`), mai hex nuovi sparsi nel codice
2. Bottoni con le classi `.btn*`, stati vuoti con `EmptyState`, messaggi con `Alert`,
   caricamenti con `Spinner`
3. Titoli con Playfair (automatico su `h1–h3`), parola chiave in `<em>`
4. Icone da `react-icons/lu`, con `aria-hidden` o `aria-label`
5. Controllare la pagina anche a 375 px di larghezza

## Area admin (Filament)
Qui conta l'organizzazione più della grafica (colori del brand già impostati in
`AdminPanelProvider`). Decisioni prese con l'utente:
- **Menu in alto** (`topNavigation()`) invece della barra laterale, e contenuto a
  **tutta larghezza** (`maxContentWidth(Width::Full)`): tutto lo schermo del PC è
  usato per tabelle e form. Su tablet e mobile il menu diventa a scomparsa
- **Salva/Annulla sempre visibili** in fondo allo schermo (`stickyFormActions()`)
  e **notifiche in basso a destra**, sulla stessa riga dei pulsanti, così la
  conferma "Salvato" si nota subito
- Nei form, le sezioni che si cambiano più spesso vanno in cima alla colonna di
  destra (es. "Stato e note" nell'ordine); la colonna laterale va tenuta corta,
  le sezioni lunghe (es. "Spedizione e tracking") vanno a sinistra
- **Pulsanti dei form allineati a destra** (`formActionsAlignment(Alignment::End)`)
- **Form piccoli** (categorie, spedizioni, sconti, comunicazioni): pagina centrata
  larga `Width::FourExtraLarge` e schema a una colonna (`->columns(1)`), così la
  sezione occupa tutto il riquadro invece di metà
- Menu su una riga: voci ravvicinate e, sotto i 1366 px, solo testo senza icone
  (`public/css/admin-custom.css`)

## Nota di sviluppo (Windows)
Con molte modifiche ravvicinate allo stesso file, il dev server Vite può continuare
a servire una versione intermedia (pagina bianca o errori tipo "X is not defined"
anche se il codice è corretto): basta riavviare `npm run dev`.
