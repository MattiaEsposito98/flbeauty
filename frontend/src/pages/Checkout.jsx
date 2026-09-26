import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import client from '../api/client'
import { useCart } from '../context/CartContext'

export default function Checkout() {
  const { items, total, clearCart } = useCart()
  const navigate = useNavigate()

  const [addresses, setAddresses] = useState([])
  const [shippingRates, setShippingRates] = useState([])
  const [addressId, setAddressId] = useState('')
  const [shippingRateId, setShippingRateId] = useState('')
  const [shippingAutoSuggested, setShippingAutoSuggested] = useState(false)
  const [discountCode, setDiscountCode] = useState('')
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    client.get('/addresses').then(({ data }) => {
      setAddresses(data)
      const defaultAddress = data.find((a) => a.is_default) ?? data[0]
      if (defaultAddress) setAddressId(String(defaultAddress.id))
    })
    client.get('/shipping-rates').then(({ data }) => {
      setShippingRates(data)
    })
  }, [])

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
  const grandTotal = total + (shippingRate ? Number(shippingRate.price) : 0)

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setSubmitting(true)

    try {
      const { data } = await client.post('/orders', {
        address_id: Number(addressId),
        shipping_rate_id: Number(shippingRateId),
        discount_code: discountCode || undefined,
        items: items.map((item) => ({ product_id: item.product.id, quantity: item.quantity })),
      })
      clearCart()
      navigate(`/ordini/${data.id}`)
    } catch (err) {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(' ') : "Errore durante l'ordine.")
    } finally {
      setSubmitting(false)
    }
  }

  if (items.length === 0) {
    return <p>Il carrello è vuoto.</p>
  }

  if (addresses.length === 0) {
    return <p>Caricamento indirizzi...</p>
  }

  return (
    <div className="page page-checkout">
      <h1>Completa l'ordine</h1>

      <form onSubmit={handleSubmit}>
        <div className="field">
          <label>Indirizzo di spedizione *</label>
          <select value={addressId} onChange={(e) => setAddressId(e.target.value)} required>
            {addresses.map((address) => (
              <option key={address.id} value={address.id}>
                {address.label || 'Indirizzo'} — {address.address_line}, {address.comune?.name}
                {address.is_default ? ' (principale)' : ''}
              </option>
            ))}
          </select>
        </div>

        <div className="field">
          <label>Spedizione *</label>
          <select
            value={shippingRateId}
            onChange={(e) => {
              setShippingRateId(e.target.value)
              setShippingAutoSuggested(false)
            }}
            required
          >
            {shippingRates.map((rate) => (
              <option key={rate.id} value={rate.id}>
                {rate.name} — &euro;{Number(rate.price).toFixed(2)}
              </option>
            ))}
          </select>
          {shippingAutoSuggested && (
            <p className="hint">Tariffa suggerita in base alla regione del tuo indirizzo. Puoi cambiarla.</p>
          )}
        </div>

        <div className="field">
          <label>Codice sconto</label>
          <input value={discountCode} onChange={(e) => setDiscountCode(e.target.value)} />
          <p className="hint">Se inserito, lo sconto verrà applicato e mostrato nel riepilogo finale.</p>
        </div>

        <div className="checkout-summary">
          <p>Subtotale: &euro;{total.toFixed(2)}</p>
          <p>Spedizione: &euro;{shippingRate ? Number(shippingRate.price).toFixed(2) : '0.00'}</p>
          <p className="cart-total">Totale: &euro;{grandTotal.toFixed(2)}</p>
        </div>

        {error && <p className="error">{error}</p>}

        <button type="submit" disabled={submitting}>
          {submitting ? 'Invio ordine...' : 'Conferma ordine'}
        </button>
      </form>
    </div>
  )
}
