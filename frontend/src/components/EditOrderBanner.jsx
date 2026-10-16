import { useNavigate } from 'react-router-dom'
import { LuPencil } from 'react-icons/lu'
import { useCart } from '../context/CartContext'
import { formatOrderNumber } from '../utils/format'

// Striscia sempre visibile mentre si modifica un ordine: ricorda che i prodotti aggiunti vanno
// nell'ordine (non nel carrello normale) e permette di salvare o uscire da qualsiasi pagina.
export default function EditOrderBanner() {
  const { editing, stopEditing, count } = useCart()
  const navigate = useNavigate()

  if (!editing) return null

  const number = formatOrderNumber(editing.orderId)

  function exit() {
    const id = editing.orderId
    stopEditing()
    navigate(`/ordini/${id}`)
  }

  return (
    <div className="edit-banner" role="status">
      <div className="edit-banner-text">
        <LuPencil aria-hidden="true" />
        <span className="edit-banner-long">
          Stai modificando l'ordine <strong>{number}</strong>: i prodotti che aggiungi vanno in questo ordine ({count}{' '}
          {count === 1 ? 'pezzo' : 'pezzi'} adesso).
        </span>
        <span className="edit-banner-short">
          Modifichi l'ordine <strong>{number}</strong> · {count} {count === 1 ? 'pezzo' : 'pezzi'}
        </span>
      </div>
      <div className="edit-banner-actions">
        <button type="button" className="btn btn-primary btn-sm" onClick={() => navigate('/checkout')}>
          Riepilogo e salva
        </button>
        <button type="button" className="btn btn-outline btn-sm" onClick={exit}>
          Esci senza salvare
        </button>
      </div>
    </div>
  )
}
