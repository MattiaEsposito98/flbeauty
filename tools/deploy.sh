#!/bin/bash
# Deploy di FLBeauty su Aruba (www.flbeauty.it). Da lanciare dalla radice del progetto, con Git Bash:
#
#   bash tools/deploy.sh            # backend + negozio
#   bash tools/deploy.sh backend    # solo backend (git pull sul server, composer, migration, cache)
#   bash tools/deploy.sh frontend   # solo negozio (build + upload di frontend/dist)
#
# Prima di partire controlla che il lavoro sia committato e pubblicato su GitHub, perché il
# backend si aggiorna scaricando da GitHub. La chiave SSH NON sta nel repository: è sul PC in
# ~/.ssh/aruba_claude (vedi CLAUDE.md). Variabili facoltative: FLBEAUTY_SSH_KEY, FLBEAUTY_SSH_USER,
# FLBEAUTY_SSH_HOST.

set -euo pipefail
cd "$(dirname "$0")/.."

TARGET="${1:-all}"
KEY="${FLBEAUTY_SSH_KEY:-$HOME/.ssh/aruba_claude}"
HOST="${FLBEAUTY_SSH_HOST:-p5pws5i.zonep5.webhostingaruba.it}"
SSH_USER="${FLBEAUTY_SSH_USER:-k29tzap-claude}"
PORT=2222
SITE="https://www.flbeauty.it"

case "$TARGET" in all | backend | frontend) ;; *)
  echo "Uso: bash tools/deploy.sh [all|backend|frontend]"
  exit 2
  ;;
esac

ssh_run() { ssh -o BatchMode=yes -o ConnectTimeout=15 -i "$KEY" -p "$PORT" "$SSH_USER@$HOST" "$@"; }

echo "== 0. Controlli iniziali"
[ -f "$KEY" ] || { echo "Chiave SSH non trovata: $KEY (vedi CLAUDE.md, sezione «Nuovo PC»)"; exit 1; }

if [ -n "$(git status --porcelain)" ]; then
  echo "Ci sono modifiche non committate: fai prima commit e push."
  git status --short
  exit 1
fi

git fetch -q origin
if [ -n "$(git rev-list origin/main..HEAD)" ]; then
  echo "Ci sono commit non pubblicati su GitHub: fai prima git push."
  exit 1
fi

[ "$(ssh_run 'echo ok' 2>/dev/null || true)" = "ok" ] || { echo "Connessione SSH non riuscita (chiave non attiva o utente eliminato in Aruba?)"; exit 1; }
echo "ok: lavoro pubblicato e server raggiungibile"

if [ "$TARGET" = "all" ] || [ "$TARGET" = "backend" ]; then
  echo "== 1. Backend (git pull sul server, composer, migration, cache, sitemap)"
  ssh_run 'bash repo/tools/aruba-update-backend.sh' 2>&1 | grep -v '⇂' | tail -n 25
fi

if [ "$TARGET" = "all" ] || [ "$TARGET" = "frontend" ]; then
  echo "== 2. Negozio (build + upload)"
  [ -f frontend/.env.production ] || { echo "Manca frontend/.env.production (indirizzo API e ID Analytics): vedi CLAUDE.md"; exit 1; }
  (cd frontend && npm run build 2>&1 | tail -n 3)
  # Carica sopra i file esistenti, senza cancellare nulla (backend, repo, sitemap.xml, collegamenti).
  (cd frontend/dist && scp -q -P "$PORT" -i "$KEY" -o BatchMode=yes -r . "$SSH_USER@$HOST:.")
  echo "upload completato"
fi

echo "== 3. Controlli sul sito online"
fail=0
check() { # percorso, codici accettati (es. "200"), descrizione
  local code
  code=$(curl -s -o /dev/null -m 30 -w "%{http_code}" "$SITE$1" || echo 000)
  if [[ " $2 " == *" $code "* ]]; then echo "  ok   $code  $1  ($3)"; else echo "  ERRORE $code  $1  ($3, atteso: $2)"; fail=1; fi
}
check "/" "200" "negozio"
check "/api/categories" "200" "API"
check "/admin/login" "200" "pannello"
check "/backend/.env" "403 404" "il backend deve restare chiuso"
check "/repo/.git/config" "403 404" "il clone deve restare chiuso"

if [ "$fail" -ne 0 ]; then
  echo "Deploy terminato con ERRORI: controlla i punti sopra."
  exit 1
fi

echo "Deploy completato. Se la home sembra vecchia: Aruba → Velocità → Caching → svuota cache."
