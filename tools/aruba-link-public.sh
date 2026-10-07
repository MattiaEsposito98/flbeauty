#!/bin/bash
# Collega alla cartella pubblica le risorse del backend che il browser deve poter aprire
# direttamente: stile e script del pannello admin, immagini e foto dei prodotti.
# Da lanciare via SSH, dopo aver caricato il backend e il contenuto di dist/.
#
# Uso:   bash repo/tools/aruba-link-public.sh [cartella-pubblica]
# La cartella pubblica è quella che contiene `backend` (su Aruba: la cartella `home`,
# cioè quella in cui ti trovi appena entri via SSH). Di default è la cartella corrente.
#
# Se l'hosting non permette i collegamenti simbolici, lo script copia i file: in quel
# caso va rilanciato dopo ogni aggiornamento del backend (composer install / git pull).

set -u
WEB="${1:-.}"
BACKEND="$WEB/backend"

if [ ! -d "$BACKEND/public" ]; then
  echo "Non trovo $BACKEND/public."
  echo "Lancia lo script dalla cartella pubblica (quella che contiene 'backend'),"
  echo "oppure passa il suo percorso come argomento."
  exit 1
fi

link() {
  local source="$1" name="$2"
  local target="$WEB/$name"

  if [ -e "$target" ] || [ -L "$target" ]; then
    echo "già presente: $name"
    return
  fi

  if ln -s "$(cd "$source" && pwd)" "$target" 2>/dev/null; then
    echo "collegato:    $name"
  else
    cp -r "$source" "$target" && echo "copiato:      $name (collegamento non permesso)"
  fi
}

mkdir -p "$BACKEND/storage/app/public"

link "$BACKEND/public/css"    css
link "$BACKEND/public/js"     js
link "$BACKEND/public/fonts"  fonts
link "$BACKEND/public/images" images
link "$BACKEND/storage/app/public" storage

echo "Fatto. Controlla: https://www.flbeauty.it/images/logo-mark.png deve aprire il logo."
