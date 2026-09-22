# FLBeauty

Progetto e-commerce "senza acquisto": gli utenti selezionano prodotti e li mettono nel
carrello, l'ordine genera solo una notifica per l'admin. Pannello admin per gestione
articoli, ordini e sconti.

## Struttura

- `backend/` — API Laravel (Sanctum) + pannello admin Filament (`/admin`)
- `frontend/` — app React (SPA) che consumerà le API del backend — da sviluppare

## Backend — avvio rapido

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Pannello admin: `http://localhost:8000/admin`
