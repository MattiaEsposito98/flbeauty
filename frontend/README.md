# FLBeauty — Frontend

App React (SPA, Vite) che consuma le API del backend Laravel in `../backend`.

## Avvio rapido

```bash
npm install
cp .env.example .env
npm run dev
```

Richiede il backend avviato su `http://127.0.0.1:8000` (vedi `../backend/README.md`).

## Cosa c'è oggi
- Registrazione utente con username + indirizzo di spedizione principale obbligatorio
  (autocomplete comune collegato alla tabella `comuni` del backend)
- Privacy e cookie policy, consenso marketing in registrazione e nel profilo
- Login con email **o** username / logout (token Sanctum salvato in `localStorage`), recupero password
  ("password dimenticata")
- Ricerca prodotti nel catalogo (nome + descrizione), combinabile col filtro categoria
- Pagina account: elenco indirizzi, aggiunta, modifica, eliminazione, impostazione
  indirizzo principale
- Preferiti (wishlist) e carrello salvati sull'account, carrello laterale
- Routing con `react-router-dom`, chiamate API con `axios`
- Tema grafico "boutique beauty" (rose-gold, Playfair Display + Jost, icone
  `react-icons`): regole in [`../docs/DESIGN.md`](../docs/DESIGN.md)

## Struttura
- `src/api/client.js` — istanza axios con token Bearer automatico
- `src/context/` — `AuthContext`, `CartContext`, `WishlistContext`
- `src/components/` — componenti riutilizzabili (header, footer, card prodotto,
  carrello laterale, form indirizzo, stepper, alert, stati vuoti...)
- `src/pages/` — una pagina per rotta (catalogo, prodotto, carrello, checkout,
  account, preferiti, login, registrazione, recupero password, ordine)
- `src/config/contacts.js` — contatti mostrati sul sito (WhatsApp, email, TikTok)
- `src/utils/format.js` — formattazione prezzi e ordini
- `src/index.css` — design system (token + stili di tutti i componenti)
