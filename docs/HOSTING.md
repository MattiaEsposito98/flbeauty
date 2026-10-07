# Hosting — decisioni prese

Ultimo aggiornamento: 2026-10-07 (procedura di messa online: [DEPLOY.md](DEPLOY.md))

## Piano scelto: Aruba Hosting Linux Advanced

Deciso con l'utente dopo confronto tra hosting condiviso e VPS (vedi ragionamento sotto).

- Prezzo: €19,90 + IVA il primo anno, rinnovo da €79,99 + IVA/anno
- Include: 1 dominio + SSL, spazio/traffico illimitati, **accesso SSH** (necessario per
  comandi Laravel: `artisan migrate`, configurazione cron, ecc.), 5 database MySQL,
  caselle email illimitate, 5 caselle PEC
- Fonte prezzi: `hosting.aruba.it/web-hosting/linux`, verificato il 2026-09-26 — i
  prezzi Aruba cambiano spesso, ricontrollare prima dell'acquisto effettivo

## Perché hosting condiviso e non VPS
- VPS (Aruba Cloud, da ~€2-6+ IVA/mese) dà un "processo sempre acceso" per la coda
  email (invio istantaneo) invece che a cron, ma richiede configurazione manuale
  completa (PHP, MySQL, web server, sicurezza, cron) e manutenzione continua
- Per un e-commerce senza pagamenti online e traffico iniziale contenuto, il ritardo
  di qualche minuto nell'invio email (dovuto al cron invece di un worker permanente)
  è accettabile
- L'hosting condiviso Advanced ha già SSH per lavorare via terminale quando serve,
  senza il carico di gestire un intero server da zero
- Si può sempre migrare a un VPS in futuro se il progetto cresce molto

## Architettura di dominio prevista
- `flbeauty.it` → frontend (negozio, React)
- `flbeauty.it/admin` e `flbeauty.it/api` → backend Laravel (pannello Filament + API), nello stesso indirizzo del negozio. Il sottodominio `admin.` non si usa: su Aruba costa 15 €/anno a parte (vedi DEPLOY.md)
- I cron (scheduler Laravel + worker delle code email) girano sullo stesso hosting,
  configurati dal pannello Aruba

## Secondo progetto futuro sullo stesso hosting
Lo spazio del piano Advanced è illimitato, quindi in teoria un secondo progetto può
essere ospitato come "dominio aggiuntivo/parcheggiato" nello stesso pacchetto,
pagando solo la registrazione del nuovo dominio (non un secondo hosting).

**Da verificare prima di contarci**: al 2026-09-26 le pagine pubbliche Aruba non
specificano il numero di domini aggiuntivi inclusi nel piano Advanced — conviene
confermare con l'assistenza Aruba (chat/telefono) quanti domini aggiuntivi sono
supportati prima di pianificare un secondo progetto su questo hosting.

## Collegato a
- [Email — setup e piano Brevo](EMAIL.md)
