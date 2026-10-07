#!/bin/bash
# Collega alla cartella pubblica (www.flbeauty.it) le risorse del backend che il
# browser deve poter aprire direttamente: stile e script del pannello admin, immagini
# e foto dei prodotti. Da lanciare via SSH, dopo aver caricato backend/ e dist/.
#
# Uso:   bash backend/../tools/aruba-link-public.sh [cartella-spazio-web]
# La cartella dello spazio web è quella che contiene sia "backend" sia "www.flbeauty.it"
# (di default la cartella corrente).
#
# Se l'hosting non permette i collegamenti simbolici, lo script copia i file: in quel
# caso va rilanciato dopo ogni aggiornamento del backend (composer install / git pull).

set -u
BASE="${1:-.}"
BACKEND="$BASE/backend"
WEB="$BASE/www.flbeauty.it"

if [ ! -d "$BACKEND/public" ] || [ ! -d "$WEB" ]; then
  echo "Non trovo $BACKEND/public e $WEB. Lancia lo script dalla cartella che contiene"
  echo "'backend' e 'www.flbeauty.it', oppure passa il percorso come argomento."
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

echo "Fatto. Controlla: https://flbeauty.it/images/logo-mark.png deve aprire il logo."
