import { useEffect, useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import { LuHeartHandshake, LuReceipt } from 'react-icons/lu'
import client from '../api/client'
import Spinner from '../components/Spinner'
import { ORDER_STATUS_LABELS, formatOrderNumber, formatPrice } from '../utils/format'

export default function OrderDetail() {
  const { id } = useParams()
  const location = useLocation()
  const justPlaced = location.state?.justPlaced === true
  const [order, setOrder] = useState(null)

  useEffect(() => {
    client.get(`/orders/${id}`).then(({ data }) => setOrder(data))
  }, [id])

  if (!order) return <Spinner />

  return (
    <div className="page order-page">
      <header className="order-intro">
        {justPlaced && (
          <span className="icon-circle icon-circle-lg" aria-hidden="true">
            <LuHeartHandshake />
          </span>
        )}
        <span className="eyebrow">Ordine {formatOrderNumber(order.id)}</span>
        <h1>{justPlaced ? 'Grazie per il tuo ordine!' : 'Dettaglio ordine'}</h1>
        <p className="page-subtitle">
          {justPlaced
            ? "Ti abbiamo mandato una email di conferma. Ti aggiorneremo sullo stato dell'ordine."
            : `Effettuato il ${new Date(order.created_at).toLocaleDateString('it-IT', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
              })}.`}
        </p>
      </header>

      <section className="card">
        <div className="card-header">
          <h2 className="card-title">
            <LuReceipt aria-hidden="true" /> Riepilogo
          </h2>
          <span className={`badge status-badge status-${order.status}`}>
            {ORDER_STATUS_LABELS[order.status] ?? order.status}
          </span>
        </div>

        <ul className="summary-items">
          {order.items.map((item) => (
            <li key={item.id}>
              <span>
                {item.product?.name ?? 'Prodotto rimosso'} <span className="muted">× {item.quantity}</span>
              </span>
              <span>{formatPrice(item.quantity * item.unit_price)}</span>
            </li>
          ))}
        </ul>

        <div className="summary-lines">
          {order.shipping_rate && (
            <div className="summary-row">
              <span>Spedizione · {order.shipping_rate.name}</span>
              <span>{formatPrice(order.shipping_rate.price)}</span>
            </div>
          )}
          {order.discount && (
            <div className="summary-row">
              <span>Codice sconto</span>
              <span>{order.discount.code}</span>
            </div>
          )}
          <div className="summary-row summary-total">
            <span>Totale</span>
            <strong>{formatPrice(order.total)}</strong>
          </div>
        </div>
      </section>

      <div className="order-actions">
        <Link to="/account" className="btn btn-outline">
          I tuoi ordini
        </Link>
        <Link to="/" className="btn btn-primary">
          Continua lo shopping
        </Link>
      </div>
    </div>
  )
}
