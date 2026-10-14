// Seed dell'ordine mescolato della home: nuovo a ogni visita (scheda/sessione), uguale finché
// si naviga, così pagina 2, il tasto «indietro» e un ricaricamento mostrano gli stessi prodotti.
// I motori di ricerca non lo ricevono: per loro l'ordine resta fisso (alfabetico).
const KEY = 'fl-home-seed'
const BOT = /bot|crawl|spider|slurp|bing|google|facebookexternalhit|lighthouse|headless/i

let memorySeed = null

function newSeed() {
  const bytes = new Uint8Array(8)
  crypto.getRandomValues(bytes)
  return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('')
}

export function getHomeSeed() {
  if (BOT.test(navigator.userAgent)) return null

  try {
    const stored = sessionStorage.getItem(KEY)
    if (stored) return stored
    const seed = newSeed()
    sessionStorage.setItem(KEY, seed)
    return seed
  } catch {
    // sessionStorage non disponibile (navigazione privata, blocchi): vale per questa pagina.
    memorySeed ??= newSeed()
    return memorySeed
  }
}
