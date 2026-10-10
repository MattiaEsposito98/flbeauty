# FLBeauty

Progetto e-commerce "senza acquisto": gli utenti selezionano prodotti e li mettono nel
carrello, l'ordine genera solo una notifica per l'admin. Pannello admin per gestione
articoli, ordini e sconti.

## Struttura

- `backend/` — API Laravel (Sanctum) + pannello admin Filament (`/admin`)
- `frontend/` — app React (SPA, Vite) che consuma le API del backend

## Backend — avvio rapido

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Pannello admin: `http://localhost:8000/admin`

## Frontend — avvio rapido

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Documentazione
- [Email (setup locale, Comunicazioni, piano produzione)](docs/EMAIL.md)
- [Hosting (piano scelto, architettura domini)](docs/HOSTING.md)
- [Frontend (stack, registrazione, indirizzi, comuni)](docs/FRONTEND.md)
- [Design system (colori, font, icone, componenti del sito)](docs/DESIGN.md)
- [Istruzioni per Claude e deploy automatico](CLAUDE.md) · [Messa online: procedura su Aruba](docs/DEPLOY.md) e [lista di cose da fare](docs/TODO-ONLINE.md)
- [SEO (Google, sitemap, dati strutturati, cosa fare dopo la messa online)](docs/SEO.md)
- [Sicurezza (limite tentativi, anti-bot, sessioni)](docs/SECURITY.md)
- [Privacy, cookie e consenso marketing](docs/PRIVACY.md)
