import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import { LuShoppingBag } from 'react-icons/lu'
import { formatOrderNumber } from '../utils/format'

// Il cliente ha già un carrello e vuole modificare un ordine: cosa facciamo del carrello?
export default function EditOrderDialog({ order, cartCount, busy, onChoose, onCancel }) {
  const firstRef = useRef(null)

  useEffect(() => {
    function handleKeyDown(e) {
      if (e.key === 'Escape' && !busy) onCancel()
    }

    document.addEventListener('keydown', handleKeyDown)
    document.body.style.overflow = 'hidden'
    firstRef.current?.focus()

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = ''
    }
  }, [busy])

  return createPortal(
    <div
      className="modal-backdrop"
      onClick={(e) => {
        if (e.target === e.currentTarget && !busy) onCancel()
      }}
    >
      <div className="modal" role="dialog" aria-modal="true" aria-labelledby="edit-order-title">
        <span className="icon-circle icon-circle-lg modal-icon">
          <LuShoppingBag aria-hidden="true" />
        </span>
        <h2 id="edit-order-title">
          Hai già dei prodotti nel <em>carrello</em>
        </h2>
        <p className="modal-lead">
          Nel carrello hai {cartCount} {cartCount === 1 ? 'pezzo' : 'pezzi'}. Cosa vuoi fare mentre modifichi l'ordine{' '}
          {formatOrderNumber(order.id)}?
        </p>

        <div className="edit-choices">
          <button type="button" ref={firstRef} className="edit-choice" onClick={() => onChoose('keep')} disabled={busy}>
            <strong>Lascia il carrello da parte</strong>
            <span>Resta com'è e lo ritrovi quando finisci di modificare l'ordine.</span>
          </button>
          <button type="button" className="edit-choice" onClick={() => onChoose('add')} disabled={busy}>
            <strong>Aggiungi i prodotti all'ordine</strong>
            <span>Quello che hai nel carrello entra nell'ordine; il carrello si svuota quando salvi.</span>
          </button>
          <button type="button" className="edit-choice" onClick={() => onChoose('clear')} disabled={busy}>
            <strong>Svuota il carrello</strong>
            <span>Il carrello attuale viene cancellato e modifichi solo l'ordine.</span>
          </button>
        </div>

        <button type="button" className="btn btn-ghost btn-block" onClick={onCancel} disabled={busy}>
          Annulla
        </button>
      </div>
    </div>,
    document.body,
  )
}
