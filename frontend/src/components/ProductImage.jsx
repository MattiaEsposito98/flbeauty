import { useState } from 'react'
import { LuSparkles } from 'react-icons/lu'

// Se l'immagine manca o non si carica (es. file rimosso dallo storage),
// mostra il placeholder del brand invece dell'icona "immagine rotta".
export default function ProductImage({ src, alt = '', compact = false, ...props }) {
  const [failedSrc, setFailedSrc] = useState(null)

  if (!src || failedSrc === src) {
    return compact ? (
      <LuSparkles aria-hidden="true" />
    ) : (
      <span className="product-placeholder" aria-hidden="true">
        <LuSparkles />
        <span>Immagine in arrivo</span>
      </span>
    )
  }

  return <img src={src} alt={alt} onError={() => setFailedSrc(src)} {...props} />
}
