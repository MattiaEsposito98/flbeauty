import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import client from '../api/client'

const STATUS_LABELS = {
  nuovo: 'Nuovo',
  in_lavorazione: 'In lavorazione',
  evaso: 'Evaso',
  annullato: 'Annullato',
}

export default function OrderDetail() {
  const { id } = useParams()
  const [order, setOrder] = useState(null)

  useEffect(() => {
    client.get(`/orders/${id}`).then(({ data }) => setOrder(data))
  }, [id])

  if (!order) return <p>Caricamento...</p>

  return (
    <div className="page page-order">
      <h1>Ordine #{String(order.id).padStart(5, '0')}</h1>
      <p className="badge">{STATUS_LABELS[order.status] ?? order.status}</p>

      <ul className="cart-list">
        {order.items.map((item) => (
          <li key={item.id}>
            <span className="cart-name">{item.product?.name ?? 'Prodotto rimosso'}</span>
            <span>x{item.quantity}</span>
            <span className="cart-price">&euro;{(item.quantity * item.unit_price).toFixed(2)}</span>
          </li>
        ))}
      </ul>

      {order.shipping_rate && <p>Spedizione: {order.shipping_rate.name}</p>}
      {order.discount && <p>Sconto applicato: {order.discount.code}</p>}
      <p className="cart-total">Totale: &euro;{Number(order.total).toFixed(2)}</p>

      <p className="hint">Ti abbiamo mandato una email di conferma. Ti aggiorneremo sullo stato dell'ordine.</p>
    </div>
  )
}
