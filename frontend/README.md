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
- Login / logout (token Sanctum salvato in `localStorage`)
- Pagina account: elenco indirizzi, aggiunta, modifica, eliminazione, impostazione
  indirizzo principale
- Routing con `react-router-dom`, chiamate API con `axios`

## Struttura
- `src/api/client.js` — istanza axios con token Bearer automatico
- `src/context/AuthContext.jsx` — stato utente/autenticazione globale
- `src/components/` — `Navbar`, `ComuneAutocomplete`, `AddressForm`
- `src/pages/` — `Home`, `Login`, `Register`, `Account`
