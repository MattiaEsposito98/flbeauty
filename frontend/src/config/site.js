// Dati del sito usati per SEO (titoli, canonical, anteprime sui social).
// Il dominio definitivo è flbeauty.it; in sviluppo si può cambiare con VITE_SITE_URL.
export const SITE_URL = (import.meta.env.VITE_SITE_URL || 'https://www.flbeauty.it').replace(/\/$/, '')

export const SITE_NAME = 'F&L Beauty'

export const DEFAULT_TITLE = 'F&L Beauty | Prodotti beauty scelti con cura'

export const DEFAULT_DESCRIPTION =
  'F&L Beauty: prodotti beauty scelti con cura, per prenderti cura di te ogni giorno. Ordina online e ricevi a casa tua.'

// Immagine di anteprima quando si condivide un link senza foto prodotto.
export const DEFAULT_IMAGE = `${SITE_URL}/logo-mark-360.webp`
