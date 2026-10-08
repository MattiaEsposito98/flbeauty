import { useEffect, useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import { FaWhatsapp } from 'react-icons/fa6'
import { LuCheck, LuCopy, LuExternalLink, LuHeartHandshake, LuMapPin, LuReceipt, LuTruck } from 'react-icons/lu'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/Spinner'
import { whatsappUrl } from '../config/contacts'
import { ORDER_STATUS_LABELS, formatOrderNumber, formatPrice } from '../utils/format'
import { formatShippingAddress, orderAmounts, orderWhatsAppText } from '../utils/orderSummary'

export default function OrderDetail() {
  const { id } = useParams()
  const location = useLocation()
  const { user } = useAuth()
  const justPlaced = location.state?.justPlaced === true
  const [order, setOrder] = useState(null)
  const [copied, setCopied] = useState(false)

  useEffect(() => {
    client.get(`/orders/${id}`).then(({ data }) => setOrder(data))
  }, [id])

  async function copyTracking() {
    await navigator.clipboard.writeText(order.tracking_number)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  if (!order) return <Spinner />

  const { subtotal, shipping, discount, total } = orderAmounts(order)
  const address = formatShippingAddress(order)
  const cancelled = order.status === 'annullato'

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

      {!cancelled && (
        <section className="card whatsapp-card">
          <span className="icon-circle" aria-hidden="true">
            <FaWhatsapp />
          </span>
          <div className="whatsapp-card-text">
            <h2>Vuoi mandarci il riepilogo su WhatsApp?</h2>
            <p className="hint">
              Si apre WhatsApp con il messaggio già pronto: prodotti, totale, spedizione e i tuoi dati. Ti basta
              premere invio.
            </p>
          </div>
          <a
            href={whatsappUrl(orderWhatsAppText(order, user))}
            className="btn btn-primary"
            target="_blank"
            rel="noopener noreferrer"
          >
            <FaWhatsapp aria-hidden="true" /> Invia su WhatsApp
          </a>
        </section>
      )}

      {order.tracking_number && (
        <section className="card tracking-card">
          <h2 className="card-title">
            <LuTruck aria-hidden="true" /> La tua spedizione
          </h2>
          <div className="tracking-details">
            {order.carrier && (
              <div>
                <span className="muted">Corriere</span>
                <strong>{order.carrier}</strong>
              </div>
            )}
            <div>
              <span className="muted">Numero di tracking</span>
              <span className="tracking-number-row">
                <strong className="tracking-number">{order.tracking_number}</strong>
                <button type="button" className="btn btn-ghost btn-sm" onClick={copyTracking}>
                  {copied ? <LuCheck aria-hidden="true" /> : <LuCopy aria-hidden="true" />}
                  {copied ? 'Copiato' : 'Copia'}
                </button>
              </span>
            </div>
          </div>
          {order.effective_tracking_url && (
            <a
              href={order.effective_tracking_url}
              className="btn btn-outline"
              target="_blank"
              rel="noopener noreferrer"
            >
              Segui la spedizione <LuExternalLink aria-hidden="true" />
            </a>
          )}
        </section>
      )}

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
                {item.product?.name ?? 'Prodotto rimosso'}
                {item.variant_name ? ` – ${item.variant_name}` : ''} <span className="muted">× {item.quantity}</span>
              </span>
              <span>{formatPrice(item.quantity * item.unit_price)}</span>
            </li>
          ))}
        </ul>

        <div className="summary-lines">
          <div className="summary-row">
            <span>Subtotale</span>
            <span>{formatPrice(subtotal)}</span>
          </div>
          {order.discount && (
            <div className="summary-row">
              <span>Sconto · {order.discount.code}</span>
              <span>−{formatPrice(discount)}</span>
            </div>
          )}
          <div className="summary-row">
            <span>Spedizione{order.shipping_rate ? ` · ${order.shipping_rate.name}` : ''}</span>
            <span>{formatPrice(shipping)}</span>
          </div>
          <div className="summary-row summary-total">
            <span>Totale</span>
            <strong>{formatPrice(total)}</strong>
          </div>
        </div>

        {address && (
          <div className="order-address">
            <LuMapPin aria-hidden="true" />
            <div>
              <strong>Spedizione a {order.customer_name}</strong>
              <p className="muted">
                {address}
                {order.customer_phone && ` · Tel. ${order.customer_phone}`}
              </p>
            </div>
          </div>
        )}
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
