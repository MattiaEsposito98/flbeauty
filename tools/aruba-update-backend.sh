#!/bin/bash
# Aggiorna il backend su Aruba dopo un `git push` (via SSH, dalla cartella `home`):
#   bash repo/tools/aruba-update-backend.sh
#
# Non tocca il file .env, il database dei dati né le foto caricate (storage): quelli
# non sono nel repository, quindi `git pull` non li sovrascrive.
# Il negozio (frontend) NON si aggiorna da qui: si costruisce sul PC (`npm run build`)
# e si carica la cartella `frontend/dist` (anche con tasto destro → Upload).

set -e
cd "$(dirname "$0")/../.."   # `home`, la cartella che contiene repo/ e backend

echo "== 1. Scarico le novità da GitHub"
git -C repo pull --ff-only

cd backend

echo "== 2. Librerie PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "== 3. Database (nuove migration, se ce ne sono)"
php artisan migrate --force

echo "== 4. Risorse del pannello e cache"
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== 5. Sitemap"
php artisan sitemap:generate

echo "== Fatto. Se hai cambiato il negozio, carica anche frontend/dist."
