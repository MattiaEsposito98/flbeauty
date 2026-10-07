import { useEffect } from 'react'
import { DEFAULT_DESCRIPTION, DEFAULT_IMAGE, DEFAULT_TITLE, SITE_NAME, SITE_URL } from '../config/site'

// Imposta titolo, descrizione, link canonical, anteprime social (Open Graph),
// robots e dati strutturati (JSON-LD) della pagina corrente. Il sito è una SPA:
// questi tag vengono scritti nell'<head> quando la pagina si monta e tornano ai
// valori di default quando si smonta.
//
// - `title`: se manca si usa il titolo di default; altrimenti diventa "titolo | F&L Beauty"
// - `path`: percorso canonico (es. "/prodotti/rossetto"); senza si usa la pagina corrente
// - `noindex`: pagine che Google non deve mostrare (account, carrello, ricerche…)
// - `jsonLd`: oggetto (o array) schema.org
export default function Seo({
  title,
  description = DEFAULT_DESCRIPTION,
  path,
  image = DEFAULT_IMAGE,
  type = 'website',
  noindex = false,
  jsonLd,
}) {
  const fullTitle = title ? `${title} | ${SITE_NAME}` : DEFAULT_TITLE
  const canonicalPath = path ?? window.location.pathname
  const canonical = `${SITE_URL}${canonicalPath}`
  const jsonLdText = jsonLd ? JSON.stringify(jsonLd) : null

  useEffect(() => {
    document.title = fullTitle

    const restore = [
      setMeta('name', 'description', description),
      setMeta('name', 'robots', noindex ? 'noindex, follow' : 'index, follow'),
      setMeta('property', 'og:type', type),
      setMeta('property', 'og:site_name', SITE_NAME),
      setMeta('property', 'og:locale', 'it_IT'),
      setMeta('property', 'og:title', fullTitle),
      setMeta('property', 'og:description', description),
      setMeta('property', 'og:url', canonical),
      setMeta('property', 'og:image', image),
      setMeta('name', 'twitter:card', 'summary_large_image'),
      setMeta('name', 'twitter:title', fullTitle),
      setMeta('name', 'twitter:description', description),
      setMeta('name', 'twitter:image', image),
      setCanonical(canonical),
    ]

    let script = null
    if (jsonLdText) {
      script = document.createElement('script')
      script.type = 'application/ld+json'
      script.dataset.seo = 'page'
      script.textContent = jsonLdText
      document.head.appendChild(script)
    }

    return () => {
      restore.forEach((undo) => undo())
      script?.remove()
    }
  }, [fullTitle, description, canonical, image, type, noindex, jsonLdText])

  return null
}

// Crea o aggiorna un <meta>; restituisce la funzione che rimette il valore precedente.
function setMeta(attribute, key, content) {
  let element = document.head.querySelector(`meta[${attribute}="${key}"]`)
  const created = !element

  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, key)
    document.head.appendChild(element)
  }

  const previous = element.getAttribute('content')
  element.setAttribute('content', content)

  return () => {
    if (created) element.remove()
    else if (previous !== null) element.setAttribute('content', previous)
  }
}

function setCanonical(href) {
  let element = document.head.querySelector('link[rel="canonical"]')
  const created = !element

  if (!element) {
    element = document.createElement('link')
    element.setAttribute('rel', 'canonical')
    document.head.appendChild(element)
  }

  const previous = element.getAttribute('href')
  element.setAttribute('href', href)

  return () => {
    if (created) element.remove()
    else if (previous !== null) element.setAttribute('href', previous)
  }
}
