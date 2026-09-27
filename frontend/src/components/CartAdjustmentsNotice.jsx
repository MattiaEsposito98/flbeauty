import { useCart } from '../context/CartContext'
import Alert from './Alert'

export default function CartAdjustmentsNotice() {
  const { adjustments, dismissAdjustments } = useCart()

  if (adjustments.length === 0) return null

  return (
    <Alert type="warning">
      <p>
        <strong>Abbiamo aggiornato il tuo carrello</strong> perché nel frattempo la disponibilità è
        cambiata:
      </p>
      <ul className="adjustments-list">
        {adjustments.map((change) => (
          <li key={change.productId}>
            {change.name}:{' '}
            {change.to > 0
              ? `ne restano ${change.to} (ne avevi ${change.from})`
              : 'esaurito, rimosso dal carrello'}
          </li>
        ))}
      </ul>
      <p>
        <button type="button" className="link-button" onClick={dismissAdjustments}>
          Ho capito
        </button>
      </p>
    </Alert>
  )
}
