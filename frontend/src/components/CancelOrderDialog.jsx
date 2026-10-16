import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import { LuTriangleAlert } from 'react-icons/lu'
import { formatOrderNumber } from '../utils/format'

// Conferma prima di annullare un ordine: l'annullamento non si può disfare dal sito.
export default function CancelOrderDialog({ order, submitting, onConfirm, onCancel }) {
  const keepRef = useRef(null)

  useEffect(() => {
    function handleKeyDown(e) {
      if (e.key === 'Escape' && !submitting) onCancel()
    }

    document.addEventListener('keydown', handleKeyDown)
    document.body.style.overflow = 'hidden'
    keepRef.current?.focus()

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = ''
    }
  }, [submitting])

  // Nel body, come OrderConfirmDialog: dentro la pagina il «fixed» si ancorerebbe a un riquadro.
  return createPortal(
    <div
      className="modal-backdrop"
      onClick={(e) => {
        if (e.target === e.currentTarget && !submitting) onCancel()
      }}
    >
      <div className="modal" role="alertdialog" aria-modal="true" aria-labelledby="cancel-order-title">
        <span className="icon-circle icon-circle-lg modal-icon">
          <LuTriangleAlert aria-hidden="true" />
        </span>
        <h2 id="cancel-order-title">
          Annullare l'ordine <em>{formatOrderNumber(order.id)}</em>?
        </h2>
        <p className="modal-lead">
          I prodotti tornano disponibili per gli altri clienti. Se vorrai riordinarli dovrai fare un nuovo ordine.
          L'annullamento non si può disfare dal sito.
        </p>
        <div className="modal-actions">
          <button type="button" ref={keepRef} className="btn btn-outline" onClick={onCancel} disabled={submitting}>
            No, tienilo
          </button>
          <button type="button" className="btn btn-primary" onClick={onConfirm} disabled={submitting}>
            {submitting ? 'Annullo...' : 'Sì, annulla l\'ordine'}
          </button>
        </div>
      </div>
    </div>,
    document.body,
  )
}
