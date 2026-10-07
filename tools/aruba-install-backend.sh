#!/bin/bash
# Primo passo della messa online del backend su Aruba (via SSH, nella cartella `home`).
# Non crea il file .env e non tocca il database: quelli vengono dopo, a blocco verificato.
#
# Cosa fa:
#   1. aggiorna il clone del progetto (repo/)
#   2. collega backend/ → repo/backend
#   3. copia nella cartella pubblica il .htaccess (che CHIUDE backend/ e repo/ al web)
#      e il file di ingresso _gateway
#   4. installa le librerie PHP con composer
#
# Uso (dalla cartella `home`):   bash repo/tools/aruba-install-backend.sh

set -e
cd "$(dirname "$0")/../.."   # la cartella che contiene repo/ (cioè `home`)

echo "== Cartella: $(pwd)"

echo "== 1. Aggiorno il clone"
git -C repo pull --ff-only

echo "== 2. Collego backend/"
if [ -e backend ] || [ -L backend ]; then
  echo "backend/ esiste già"
else
  ln -s repo/backend backend
  echo "creato: backend -> repo/backend"
fi

echo "== 3. Regole di sicurezza e ingresso"
cp repo/frontend/public/.htaccess .htaccess
rm -rf _gateway
cp -r repo/frontend/public/_gateway _gateway
echo "copiati: .htaccess e _gateway/"

echo "== 4. Installo le librerie (composer, può richiedere qualche minuto)"
cd backend
composer install --no-dev --optimize-autoloader --no-interaction

echo "== Fatto. Non c'è ancora il file .env: controlla il blocco dal web prima di crearlo."
ls -la "$(dirname "$(pwd -P)")/.."
