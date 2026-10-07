import { useEffect, useRef } from 'react'
import { LuHeart, LuMessageCircle, LuPackageCheck, LuSend, LuWallet } from 'react-icons/lu'
import { formatPrice } from '../utils/format'

export default function OrderConfirmDialog({ open, total, itemCount, submitting, onConfirm, onCancel }) {
  const cancelRef = useRef(null)

  useEffect(() => {
    if (!open) return

    function handleKeyDown(e) {
      if (e.key === 'Escape' && !submitting) onCancel()
    }

    document.addEventListener('keydown', handleKeyDown)
    document.body.style.overflow = 'hidden'
    cancelRef.current?.focus()

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = ''
    }
  }, [open, submitting])

  if (!open) return null

  return (
    <div
      className="modal-backdrop"
      onClick={(e) => {
        if (e.target === e.currentTarget && !submitting) onCancel()
      }}
    >
      <div className="modal" role="dialog" aria-modal="true" aria-labelledby="order-confirm-title">
        <span className="icon-circle icon-circle-lg modal-icon">
          <LuPackageCheck aria-hidden="true" />
        </span>
        <span className="eyebrow">Ultimo passo</span>
        <h2 id="order-confirm-title">
          Vuoi inviare il tuo <em>ordine</em>?
        </h2>
        <p className="modal-lead">
          Stai per ordinare {itemCount} {itemCount === 1 ? 'prodotto' : 'prodotti'} per un totale di{' '}
          <strong>{formatPrice(total)}</strong>.
        </p>

        <ul className="modal-steps">
          <li>
            <LuSend aria-hidden="true" />
            <span>
              Cliccando su <strong>«Sì, invia l'ordine»</strong> il tuo ordine arriva subito a noi e i
              prodotti vengono messi da parte per te.
            </span>
          </li>
          <li>
            <LuMessageCircle aria-hidden="true" />
            <span>
              Ti scriveremo su <strong>WhatsApp</strong> con le istruzioni per il pagamento: sul sito
              non devi pagare nulla.
            </span>
          </li>
          <li>
            <LuWallet aria-hidden="true" />
            <span>
              L'ordine è <strong>confermato quando riceviamo il pagamento</strong>, poi lo prepariamo e
              lo spediamo.
            </span>
          </li>
        </ul>

        <p className="modal-note">
          <LuHeart aria-hidden="true" /> Invia l'ordine solo se vuoi davvero acquistare: i prodotti
          restano riservati a te e non sono disponibili per gli altri clienti.
        </p>

        <div className="modal-actions">
          <button
            type="button"
            ref={cancelRef}
            className="btn btn-outline"
            onClick={onCancel}
            disabled={submitting}
          >
            Torna indietro
          </button>
          <button type="button" className="btn btn-primary" onClick={onConfirm} disabled={submitting}>
            {submitting ? 'Invio ordine...' : "Sì, invia l'ordine"}
          </button>
        </div>
      </div>
    </div>
  )
}
