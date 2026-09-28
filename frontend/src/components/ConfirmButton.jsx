import { useEffect, useState } from 'react'

// Chiede conferma nel pulsante stesso (primo click arma, secondo esegue)
// invece del popup del browser, che stona col tema.
export default function ConfirmButton({ onConfirm, confirmLabel, className = '', disabled = false, children }) {
  const [armed, setArmed] = useState(false)

  useEffect(() => {
    if (!armed) return
    const timeout = setTimeout(() => setArmed(false), 3000)
    return () => clearTimeout(timeout)
  }, [armed])

  function handleClick() {
    if (armed) {
      setArmed(false)
      onConfirm()
    } else {
      setArmed(true)
    }
  }

  return (
    <button
      type="button"
      className={`${className} ${armed ? 'is-armed' : ''}`}
      onClick={handleClick}
      disabled={disabled}
    >
      {armed ? confirmLabel : children}
    </button>
  )
}
