import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom'
import { FaWhatsapp } from 'react-icons/fa6'
import { LuCheck, LuCopy, LuExternalLink, LuHeartHandshake, LuMapPin, LuPencil, LuReceipt, LuTruck, LuX } from 'react-icons/lu'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import { useCart } from '../context/CartContext'
import Alert from '../components/Alert'
import CancelOrderDialog from '../components/CancelOrderDialog'
import EditOrderDialog from '../components/EditOrderDialog'
import Spinner from '../components/Spinner'
import { whatsappUrl } from '../config/contacts'
import { ORDER_STATUS_LABELS, formatOrderNumber, formatPrice, formatShippingPrice } from '../utils/format'
import { formatShippingAddress, isHandPickup, orderAmounts, orderWhatsAppText } from '../utils/orderSummary'

export default function OrderDetail() {
  const { id } = useParams()
  const location = useLocation()
  const navigate = useNavigate()
  const { user } = useAuth()
  const { editing: editState, realCount, startEditing, stopEditing } = useCart()
  const justPlaced = location.state?.justPlaced === true
  const [order, setOrder] = useState(null)
  const [copied, setCopied] = useState(false)
  const [editDialogOpen, setEditDialogOpen] = useState(false)
  const [startingEdit, setStartingEdit] = useState(false)
  const [cancelOpen, setCancelOpen] = useState(false)
  const [cancelling, setCancelling] = useState(false)
  // Dopo aver salvato una modifica dal carrello si torna qui con questo messaggio.
  const [notice, setNotice] = useState(() =>
    location.state?.justEdited
      ? "Ordine aggiornato. Ti abbiamo mandato una email con il riepilogo: ti scriviamo su WhatsApp con l'importo da pagare."
      : (location.state?.editBlocked ?? null),
  )
  const [actionError, setActionError] = useState(null)

  function loadOrder() {
    return client.get(`/orders/${id}`).then(({ data }) => setOrder(data))
  }

  useEffect(() => {
    loadOrder()
  }, [id])

  // Finché è in attesa di pagamento il cliente può modificarlo o annullarlo; con il pagamento ricevuto si blocca.
  const editable = order?.status === 'nuovo'

  // «Modifica ordine»: l'ordine si riapre come carrello e il cliente naviga il sito per aggiungere prodotti.
  // Se ha già un carrello, prima gli chiediamo cosa farne.
  function beginEdit() {
    if (realCount > 0) setEditDialogOpen(true)
    else startEdit('keep')
  }

  async function startEdit(mode) {
    setStartingEdit(true)
    setActionError(null)

    try {
      await startEditing(order, mode)
      setEditDialogOpen(false)
      navigate('/')
    } catch {
      setActionError('Non è stato possibile aprire la modifica. Riprova.')
      setEditDialogOpen(false)
    } finally {
      setStartingEdit(false)
    }
  }

  async function cancelOrder() {
    setCancelling(true)
    setActionError(null)

    try {
      const { data } = await client.post(`/orders/${id}/cancel`)
      setOrder(data)
      if (editState?.orderId === data.id) stopEditing()
      setNotice('Ordine annullato. I prodotti sono tornati disponibili.')
      window.dispatchEvent(new Event('notifications:refresh'))
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } catch (err) {
      const errors = err.response?.data?.errors
      setActionError(errors ? Object.values(errors).flat().join(' ') : "Non è stato possibile annullare l'ordine. Riprova.")
      await loadOrder()
    } finally {
      setCancelling(false)
      setCancelOpen(false)
    }
  }

  async function copyTracking() {
    await navigator.clipboard.writeText(order.tracking_number)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  if (!order) return <Spinner />

  const { subtotal, shipping, discount, total } = orderAmounts(order)
  const address = formatShippingAddress(order)
  const cancelled = order.status === 'annullato'
  const editingThis = editState?.orderId === order.id
  const editingOther = Boolean(editState) && !editingThis

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

      {notice && <Alert type="success">{notice}</Alert>}
      {actionError && <Alert type="error">{actionError}</Alert>}

      {editable && (
        <section className="card order-manage-card">
          <div>
            <h2>{editingThis ? 'Stai modificando questo ordine' : 'Puoi ancora modificare questo ordine'}</h2>
            <p className="hint">
              {editingThis
                ? "Il catalogo e il carrello lavorano su questo ordine finché non salvi o esci dalla modifica."
                : editingOther
                  ? `Stai già modificando l'ordine ${formatOrderNumber(editState.orderId)}: salvalo o esci dalla modifica prima di aprirne un altro.`
                  : 'Finché non risulta pagato puoi cambiare prodotti, indirizzo e spedizione, oppure annullarlo.'}
            </p>
          </div>
          <div className="order-manage-actions">
            {editingThis ? (
              <>
                <button type="button" className="btn btn-primary" onClick={() => navigate('/checkout')}>
                  <LuPencil aria-hidden="true" /> Riepilogo e salva
                </button>
                <button type="button" className="btn btn-outline" onClick={() => navigate('/')}>
                  Continua a scegliere i prodotti
                </button>
              </>
            ) : (
              <>
                <button type="button" className="btn btn-primary" onClick={beginEdit} disabled={editingOther || startingEdit}>
                  <LuPencil aria-hidden="true" /> {startingEdit ? 'Apro la modifica...' : 'Modifica ordine'}
                </button>
                <button type="button" className="btn btn-outline" onClick={() => setCancelOpen(true)}>
                  <LuX aria-hidden="true" /> Annulla ordine
                </button>
              </>
            )}
          </div>
        </section>
      )}

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
            <span>
              {isHandPickup(order) ? 'Ritiro a mano' : `Spedizione${order.shipping_rate ? ` · ${order.shipping_rate.name}` : ''}`}
            </span>
            <span>{formatShippingPrice(shipping)}</span>
          </div>
          <div className="summary-row summary-total">
            <span>Totale</span>
            <strong>{formatPrice(total)}</strong>
          </div>
        </div>

        {isHandPickup(order) && (
          <div className="order-address">
            <LuMapPin aria-hidden="true" />
            <div>
              <strong>Ritiro a mano</strong>
              <p className="muted">Passi tu a ritirare il pacco: ti scriviamo su WhatsApp per metterci d'accordo su giorno e orario.</p>
            </div>
          </div>
        )}

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

      {editDialogOpen && (
        <EditOrderDialog
          order={order}
          cartCount={realCount}
          busy={startingEdit}
          onChoose={startEdit}
          onCancel={() => setEditDialogOpen(false)}
        />
      )}

      {cancelOpen && (
        <CancelOrderDialog order={order} submitting={cancelling} onConfirm={cancelOrder} onCancel={() => setCancelOpen(false)} />
      )}

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
