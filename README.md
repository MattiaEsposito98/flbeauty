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
