import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LuMapPin, LuShoppingBag, LuTicket, LuTruck } from 'react-icons/lu'
import client from '../api/client'
import { useCart } from '../context/CartContext'
import Alert from '../components/Alert'
import CartAdjustmentsNotice from '../components/CartAdjustmentsNotice'
import EmptyState from '../components/EmptyState'
import OrderConfirmDialog from '../components/OrderConfirmDialog'
import Spinner from '../components/Spinner'
import { formatPrice } from '../utils/format'
import { itemName, itemPrice, lineKey } from '../utils/cart'

export default function Checkout() {
  const { items, loading: cartLoading, total, clearCart, syncAvailability } = useCart()
  const navigate = useNavigate()

  const [addresses, setAddresses] = useState([])
  const [addressesLoaded, setAddressesLoaded] = useState(false)
  const [shippingRates, setShippingRates] = useState([])
  const [addressId, setAddressId] = useState('')
  const [shippingRateId, setShippingRateId] = useState('')
  const [shippingAutoSuggested, setShippingAutoSuggested] = useState(false)
  const [discountCode, setDiscountCode] = useState('')
  // Codice già controllato dal server con «Applica»: { code, type, value }.
  const [appliedDiscount, setAppliedDiscount] = useState(null)
  const [discountError, setDiscountError] = useState(null)
  const [applyingDiscount, setApplyingDiscount] = useState(false)
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [confirmOpen, setConfirmOpen] = useState(false)

  useEffect(() => {
    client.get('/addresses').then(({ data }) => {
      setAddresses(data)
      const defaultAddress = data.find((a) => a.is_default) ?? data[0]
      if (defaultAddress) setAddressId(String(defaultAddress.id))
      setAddressesLoaded(true)
    })
    client.get('/shipping-rates').then(({ data }) => {
      setShippingRates(data)
    })
  }, [])

  useEffect(() => {
    if (!cartLoading) syncAvailability()
  }, [cartLoading])

  // Suggerisce automaticamente la tariffa della regione dell'indirizzo scelto
  // (le tariffe sono definite per regione in admin); l'utente può comunque
  // cambiarla, ad es. per scegliere il punto di ritiro.
  useEffect(() => {
    if (!addressId || shippingRates.length === 0) return

    const address = addresses.find((a) => String(a.id) === addressId)
    const region = address?.comune?.region

    const matched = shippingRates.find((r) => r.type === 'regione' && r.name === region)

    setShippingRateId(String((matched ?? shippingRates[0]).id))
    setShippingAutoSuggested(Boolean(matched))
  }, [addressId, addresses, shippingRates])

  const shippingRate = shippingRates.find((r) => String(r.id) === shippingRateId)
  // Lo sconto si applica ai prodotti, non alla spedizione (come fa il server).
  const discountAmount = appliedDiscount
    ? appliedDiscount.type === 'percentuale'
      ? Math.round(total * appliedDiscount.value) / 100
      : Math.min(appliedDiscount.value, total)
    : 0
  const grandTotal = Math.max(0, total - discountAmount) + (shippingRate ? Number(shippingRate.price) : 0)

  function changeDiscountCode(value) {
    setDiscountCode(value)
    setDiscountError(null)
    // Se il codice cambia dopo «Applica», lo sconto non vale più finché non lo si riapplica.
    if (appliedDiscount && value.trim().toLowerCase() !== appliedDiscount.code.toLowerCase()) {
      setAppliedDiscount(null)
    }
  }

  // Controlla il codice sul server PRIMA dell'ordine, così lo sconto si vede subito nel riepilogo.
  async function applyDiscount() {
    const code = discountCode.trim()
    if (!code) return null

    setApplyingDiscount(true)
    setDiscountError(null)

    try {
      const { data } = await client.post('/discounts/check', { code })
      setAppliedDiscount(data.data)
      setDiscountCode(data.data.code)
      return data.data
    } catch (err) {
      setAppliedDiscount(null)
      setDiscountError(err.response?.data?.errors?.code?.[0] ?? 'Non è stato possibile controllare il codice. Riprova.')
      return null
    } finally {
      setApplyingDiscount(false)
    }
  }

  function removeDiscount() {
    setAppliedDiscount(null)
    setDiscountCode('')
    setDiscountError(null)
  }

  // Il pulsante del form non invia l'ordine: apre prima la finestra di conferma,
  // perché il pagamento è fuori dal sito e un click per errore creerebbe un ordine
  // da annullare. Un codice scritto ma non ancora applicato viene controllato adesso.
  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)

    if (discountCode.trim() && !appliedDiscount && !(await applyDiscount())) return

    setConfirmOpen(true)
  }

  async function placeOrder() {
    setError(null)
    setSubmitting(true)

    try {
      const { data } = await client.post('/orders', {
        address_id: Number(addressId),
        shipping_rate_id: Number(shippingRateId),
        discount_code: appliedDiscount?.code ?? (discountCode.trim() || undefined),
        items: items.map((item) => ({
          product_id: item.product.id,
          product_variant_id: item.variantId,
          quantity: item.quantity,
        })),
      })
      clearCart()
      navigate(`/ordini/${data.id}`, { state: { justPlaced: true } })
    } catch (err) {
      setConfirmOpen(false)
      const errors = err.response?.data?.errors

      // Stock cambiato tra l'apertura del checkout e la conferma: riallineiamo
      // il carrello e facciamo riconfermare con le quantità aggiornate.
      if (errors?.items) {
        const changes = await syncAvailability()
        if (changes.length > 0) {
          setError('Le disponibilità sono cambiate: controlla il riepilogo aggiornato e conferma di nuovo.')
          return
        }
      }

      setError(errors ? Object.values(errors).flat().join(' ') : "Errore durante l'ordine.")
    } finally {
      setSubmitting(false)
    }
  }

  if (cartLoading) return <Spinner />

  if (items.length === 0) {
    return (
      <div className="page">
        <CartAdjustmentsNotice />
        <EmptyState
          icon={LuShoppingBag}
          title="Il carrello è vuoto"
          action={
            <Link to="/" className="btn btn-primary">
              Scopri i prodotti
            </Link>
          }
        >
          Aggiungi qualche prodotto prima di completare l'ordine.
        </EmptyState>
      </div>
    )
  }

  if (!addressesLoaded) {
    return <Spinner label="Caricamento indirizzi..." />
  }

  if (addresses.length === 0) {
    return (
      <div className="page">
        <EmptyState
          icon={LuMapPin}
          title="Manca un indirizzo di spedizione"
          action={
            <Link to="/account" className="btn btn-primary">
              Aggiungi un indirizzo
            </Link>
          }
        >
          Per completare l'ordine aggiungi prima un indirizzo di spedizione dal tuo account.
        </EmptyState>
      </div>
    )
  }

  return (
    <div className="page checkout-page">
      <header className="page-header">
        <span className="eyebrow">Checkout</span>
        <h1>
          Completa il tuo <em>ordine</em>
        </h1>
        <p className="page-subtitle">
          Controlla i dati di spedizione e conferma: ti invieremo una email di riepilogo.
        </p>
      </header>

      <form className="checkout-layout" onSubmit={handleSubmit}>
        <div className="checkout-sections">
          <section className="card">
            <h2 className="card-title">
              <LuMapPin aria-hidden="true" /> Indirizzo di spedizione
            </h2>
            <div className="field">
              <label htmlFor="checkout-address">Spedisci a *</label>
              <select
                id="checkout-address"
                value={addressId}
                onChange={(e) => setAddressId(e.target.value)}
                required
              >
                {addresses.map((address) => (
                  <option key={address.id} value={address.id}>
                    {address.label || 'Indirizzo'} — {address.address_line}, {address.comune?.name}
                    {address.is_default ? ' (principale)' : ''}
                  </option>
                ))}
              </select>
            </div>
            <Link to="/account" className="text-link">
              Gestisci i tuoi indirizzi
            </Link>
          </section>

          <section className="card">
            <h2 className="card-title">
              <LuTruck aria-hidden="true" /> Spedizione
            </h2>
            <div className="field">
              <label htmlFor="checkout-shipping">Metodo di spedizione *</label>
              <select
                id="checkout-shipping"
                value={shippingRateId}
                onChange={(e) => {
                  setShippingRateId(e.target.value)
                  setShippingAutoSuggested(false)
                }}
                required
              >
                {shippingRates.map((rate) => (
                  <option key={rate.id} value={rate.id}>
                    {rate.name} — {formatPrice(rate.price)}
                  </option>
                ))}
              </select>
              {shippingAutoSuggested && (
                <p className="hint">Tariffa suggerita in base alla regione del tuo indirizzo. Puoi cambiarla.</p>
              )}
            </div>
          </section>

          <section className="card">
            <h2 className="card-title">
              <LuTicket aria-hidden="true" /> Codice sconto
            </h2>
            <div className="field">
              <label htmlFor="checkout-discount">Hai un codice sconto?</label>
              <div className="code-field">
                <input
                  id="checkout-discount"
                  value={discountCode}
                  onChange={(e) => changeDiscountCode(e.target.value)}
                  onKeyDown={(e) => {
                    // Invio applica il codice invece di aprire la conferma dell'ordine.
                    if (e.key === 'Enter') {
                      e.preventDefault()
                      applyDiscount()
                    }
                  }}
                  placeholder="Inserisci il codice"
                  autoCapitalize="characters"
                  autoComplete="off"
                  aria-describedby="checkout-discount-hint"
                />
                {appliedDiscount ? (
                  <button type="button" className="btn btn-outline" onClick={removeDiscount}>
                    Togli
                  </button>
                ) : (
                  <button
                    type="button"
                    className="btn btn-outline"
                    onClick={applyDiscount}
                    disabled={applyingDiscount || !discountCode.trim()}
                  >
                    {applyingDiscount ? 'Controllo...' : 'Applica'}
                  </button>
                )}
              </div>
              <p id="checkout-discount-hint" className="hint" role="status">
                {appliedDiscount
                  ? `Codice applicato: −${formatPrice(discountAmount)} sul totale dei prodotti.`
                  : 'Scrivi il codice e premi «Applica»: vedrai subito lo sconto nel riepilogo.'}
              </p>
              {discountError && <p className="error">{discountError}</p>}
            </div>
          </section>
        </div>

        <aside className="card summary-card">
          <h2>Riepilogo ordine</h2>
          <ul className="summary-items">
            {items.map((item) => (
              <li key={lineKey(item.product.id, item.variantId)}>
                <span>
                  {itemName(item)} <span className="muted">× {item.quantity}</span>
                </span>
                <span>{formatPrice(itemPrice(item) * item.quantity)}</span>
              </li>
            ))}
          </ul>
          <div className="summary-lines">
            <div className="summary-row">
              <span>Subtotale</span>
              <span>{formatPrice(total)}</span>
            </div>
            {appliedDiscount && (
              <div className="summary-row summary-discount">
                <span>Sconto · {appliedDiscount.code}</span>
                <span>−{formatPrice(discountAmount)}</span>
              </div>
            )}
            <div className="summary-row">
              <span>Spedizione</span>
              <span>{shippingRate ? formatPrice(shippingRate.price) : '—'}</span>
            </div>
            <div className="summary-row summary-total">
              <span>Totale</span>
              <strong>{formatPrice(grandTotal)}</strong>
            </div>
          </div>

          <CartAdjustmentsNotice />
          {error && <Alert type="error">{error}</Alert>}

          <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
            Conferma ordine
          </button>
        </aside>
      </form>

      <OrderConfirmDialog
        open={confirmOpen}
        total={grandTotal}
        itemCount={items.length}
        submitting={submitting}
        onConfirm={placeOrder}
        onCancel={() => setConfirmOpen(false)}
      />
    </div>
  )
}
