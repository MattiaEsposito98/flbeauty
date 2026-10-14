// Seed dell'ordine mescolato della home («Tutte»). Il server mescola i prodotti in modo
// ripetibile a partire da questo codice: stesso seed = stesso ordine su tutte le pagine.
//
// Un NUOVO seed (quindi un nuovo ordine) si crea quando:
//  - si apre o si ricarica il sito;
//  - si torna su «Tutte» da un'altra categoria, o si preme di nuovo «Tutte».
// Lo STESSO seed resta quando si passa alla pagina 2 e quando si torna indietro da un prodotto
// (tasto «indietro»): altrimenti la pagina 2 avrebbe doppioni e il cliente perderebbe il punto.
//
// I motori di ricerca non lo ricevono: per loro l'ordine resta fisso (alfabetico).
const KEY = 'fl-home-seed'
const BOT = /bot|crawl|spider|slurp|bing|google|facebookexternalhit|lighthouse|headless/i

let firstCall = true
let memorySeed = null

function newSeed() {
  const bytes = new Uint8Array(8)
  crypto.getRandomValues(bytes)
  return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('')
}

function read() {
  try {
    return sessionStorage.getItem(KEY) ?? memorySeed
  } catch {
    return memorySeed
  }
}

/** Crea e ricorda un seed nuovo (nuovo ordine della home). Null per i motori di ricerca. */
export function refreshHomeSeed() {
  if (BOT.test(navigator.userAgent)) return null

  const seed = newSeed()
  memorySeed = seed
  try {
    sessionStorage.setItem(KEY, seed)
  } catch {
    // sessionStorage non disponibile (navigazione privata, blocchi): vale per questa pagina.
  }
  return seed
}

/**
 * Seed con cui parte la pagina: si riusa quello di prima solo con il tasto «indietro/avanti»
 * ('POP' per React Router, 'back_forward' per il browser); in ogni altro caso ordine nuovo.
 */
export function initialHomeSeed(navigationType) {
  // Primo caricamento del sito: decide il browser ('navigate' = apertura, 'reload' = ricarica,
  // 'back_forward' = tasto indietro/avanti). Dopo, navigando dentro il sito, decide React Router.
  const browserType = performance.getEntriesByType?.('navigation')?.[0]?.type
  const isBack = firstCall ? browserType === 'back_forward' : navigationType === 'POP'
  firstCall = false

  const stored = read()
  if (stored && isBack && !BOT.test(navigator.userAgent)) return stored

  return refreshHomeSeed()
}
