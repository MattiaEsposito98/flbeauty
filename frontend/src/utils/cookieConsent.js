// Consenso ai cookie non tecnici (oggi solo Google Analytics).
//
// Finché VITE_GA_MEASUREMENT_ID non è impostato il sito usa solo strumenti
// tecnici, che non richiedono consenso: il banner non compare e niente viene
// caricato. Con l'ID impostato compare il banner, e Analytics parte solo dopo
// "Accetta". La scelta resta salvata nel browser per 6 mesi, poi viene richiesta.

import client from '../api/client'

export const GA_ID = import.meta.env.VITE_GA_MEASUREMENT_ID
export const analyticsAvailable = Boolean(GA_ID)

const STORAGE_KEY = 'cookie_consent'
const CONSENT_VERSION = 1
const MAX_AGE_MS = 1000 * 60 * 60 * 24 * 180
const OPEN_EVENT = 'cookie-preferences-open'

export function readConsent() {
  try {
    const consent = JSON.parse(localStorage.getItem(STORAGE_KEY))
    const expired = Date.now() - new Date(consent?.date).getTime() > MAX_AGE_MS
    return consent?.version === CONSENT_VERSION && !expired ? consent : null
  } catch {
    return null
  }
}

export function saveConsent(analytics) {
  const consent = { version: CONSENT_VERSION, analytics, date: new Date().toISOString() }
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(consent))
  } catch {
    // Browser senza storage (navigazione privata restrittiva): la scelta vale
    // solo per questa visita.
  }
  return consent
}

// Statistiche anonime per l'admin (banner mostrato / accettato / rifiutato):
// il backend salva solo i totali del giorno. Un errore non deve disturbare.
export function recordBannerEvent(event) {
  client.post('/cookie-consent-stats', { event }).catch(() => {})
}

// Il link "Preferenze cookie" nel footer riapre il banner.
export function openCookiePreferences() {
  window.dispatchEvent(new Event(OPEN_EVENT))
}

export function onOpenCookiePreferences(handler) {
  window.addEventListener(OPEN_EVENT, handler)
  return () => window.removeEventListener(OPEN_EVENT, handler)
}

let gaLoaded = false

export function enableAnalytics() {
  if (!GA_ID) return
  window[`ga-disable-${GA_ID}`] = false
  if (gaLoaded) return

  gaLoaded = true
  window.dataLayer = window.dataLayer || []
  window.gtag = function gtag() {
    window.dataLayer.push(arguments)
  }
  window.gtag('js', new Date())
  // Le pagine viste le inviamo noi a ogni cambio di pagina (sito a pagina singola).
  window.gtag('config', GA_ID, { send_page_view: false })

  const script = document.createElement('script')
  script.async = true
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`
  document.head.appendChild(script)
}

export function disableAnalytics() {
  if (!GA_ID) return
  window[`ga-disable-${GA_ID}`] = true
  // Cancella i cookie di Analytics già salvati (_ga, _ga_XXXX).
  const domain = window.location.hostname.replace(/^www\./, '')
  document.cookie.split(';').forEach((cookie) => {
    const name = cookie.split('=')[0].trim()
    if (name.startsWith('_ga')) {
      document.cookie = `${name}=; Max-Age=0; path=/`
      document.cookie = `${name}=; Max-Age=0; path=/; domain=.${domain}`
    }
  })
}

export function trackPageView(path) {
  if (gaLoaded && window.gtag) {
    window.gtag('event', 'page_view', { page_path: path })
  }
}
