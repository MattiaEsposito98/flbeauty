import { useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { LuCookie } from 'react-icons/lu'
import {
  analyticsAvailable,
  disableAnalytics,
  enableAnalytics,
  onOpenCookiePreferences,
  readConsent,
  saveConsent,
  trackPageView,
} from '../utils/cookieConsent'

// Banner cookie secondo le linee guida del Garante: "Accetta" e "Rifiuta" hanno
// lo stesso peso, niente parte prima del consenso, la scelta si può cambiare
// in ogni momento dal link "Preferenze cookie" nel footer.
// Compare solo se Google Analytics è configurato (vedi utils/cookieConsent.js).
export default function CookieBanner() {
  const location = useLocation()
  const [consent, setConsent] = useState(() => (analyticsAvailable ? readConsent() : null))
  const [open, setOpen] = useState(() => analyticsAvailable && !readConsent())

  useEffect(() => onOpenCookiePreferences(() => setOpen(true)), [])

  useEffect(() => {
    if (consent?.analytics) enableAnalytics()
  }, [consent])

  useEffect(() => {
    if (consent?.analytics) trackPageView(location.pathname + location.search)
  }, [consent, location])

  function choose(analytics) {
    setConsent(saveConsent(analytics))
    if (!analytics) disableAnalytics()
    setOpen(false)
  }

  if (!analyticsAvailable || !open) return null

  return (
    <div className="cookie-banner" role="dialog" aria-live="polite" aria-labelledby="cookie-banner-title">
      <span className="icon-circle" aria-hidden="true">
        <LuCookie />
      </span>
      <div className="cookie-banner-text">
        <h2 id="cookie-banner-title">Rispettiamo la tua privacy</h2>
        <p>
          Usiamo strumenti tecnici necessari al funzionamento del sito e, solo con il tuo consenso,
          Google Analytics per capire in forma aggregata come viene visitato il sito. Puoi cambiare
          idea quando vuoi da "Preferenze cookie" in fondo alla pagina.{' '}
          <Link to="/cookie">Leggi la cookie policy</Link>
        </p>
      </div>
      <div className="cookie-banner-actions">
        <button type="button" className="btn btn-outline" onClick={() => choose(false)}>
          Rifiuta
        </button>
        <button type="button" className="btn btn-outline" onClick={() => choose(true)}>
          Accetta
        </button>
      </div>
    </div>
  )
}
