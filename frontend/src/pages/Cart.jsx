import { useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LuArrowLeft, LuShoppingBag, LuTrash2 } from 'react-icons/lu'
import { useCart } from '../context/CartContext'
import { useAuth } from '../context/AuthContext'
import CartAdjustmentsNotice from '../components/CartAdjustmentsNotice'
import ConfirmButton from '../components/ConfirmButton'
import EmptyState from '../components/EmptyState'
import ProductImage from '../components/ProductImage'
import QuantityStepper from '../components/QuantityStepper'
import Spinner from '../components/Spinner'
import { formatPrice } from '../utils/format'
import { itemImage, itemName, itemPrice, itemStock, itemVariant, lineKey } from '../utils/cart'

export default function Cart() {
  const { items, loading, updateQuantity, removeItem, clearCart, total, count, syncAvailability } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    if (!loading) syncAvailability()
  }, [loading])

  if (loading) return <Spinner />

  if (items.length === 0) {
    return (
      <div className="page">
        <CartAdjustmentsNotice />
        <EmptyState
          icon={LuShoppingBag}
          title="Il tuo carrello è vuoto"
          action={
            <Link to="/" className="btn btn-primary">
              Scopri i prodotti
            </Link>
          }
        >
          Aggiungi i prodotti che ami e li ritroverai qui.
        </EmptyState>
      </div>
    )
  }

  function handleCheckout() {
    navigate(user ? '/checkout' : '/login?redirect=/checkout')
  }

  return (
    <div className="page cart-page">
      <header className="page-header">
        <span className="eyebrow">Carrello</span>
        <h1>
          Il tuo <em>carrello</em>
        </h1>
        <p className="page-subtitle">
          {count} {count === 1 ? 'articolo pronto' : 'articoli pronti'} per te
        </p>
      </header>

      <CartAdjustmentsNotice />

      <div className="cart-layout">
        <div>
        <div className="list-toolbar">
          <ConfirmButton
            className="btn btn-ghost btn-sm btn-ghost-danger"
            confirmLabel="Sicuro? Svuota"
            onConfirm={clearCart}
          >
            <LuTrash2 aria-hidden="true" /> Svuota carrello
          </ConfirmButton>
        </div>
        <ul className="cart-items">
          {items.map((item) => (
            <li key={lineKey(item.product.id, item.variantId)} className="cart-item">
              <Link to={`/prodotti/${item.product.slug}`} className="item-thumb" tabIndex={-1}>
                <ProductImage src={itemImage(item)} compact />
              </Link>
              <div className="cart-item-info">
                <Link to={`/prodotti/${item.product.slug}`} className="item-name">
                  {item.product.name}
                </Link>
                {itemVariant(item) && (
                  <span className="item-variant">
                    {item.product.variant_label}: {itemVariant(item).name}
                  </span>
                )}
                <span className="item-unit-price">{formatPrice(itemPrice(item))} cad.</span>
              </div>
              <QuantityStepper
                value={item.quantity}
                max={itemStock(item)}
                onChange={(quantity) => updateQuantity(item.product.id, item.variantId, quantity)}
              />
              <span className="item-total">{formatPrice(itemPrice(item) * item.quantity)}</span>
              <button
                type="button"
                className="icon-btn remove-btn"
                onClick={() => removeItem(item.product.id, item.variantId)}
                aria-label={`Rimuovi ${itemName(item)}`}
              >
                <LuTrash2 aria-hidden="true" />
              </button>
            </li>
          ))}
        </ul>
        </div>

        <aside className="card summary-card">
          <h2>Riepilogo</h2>
          <div className="summary-lines">
            <div className="summary-row">
              <span>Subtotale</span>
              <span>{formatPrice(total)}</span>
            </div>
            <div className="summary-row">
              <span>Spedizione</span>
              <span>Calcolata al checkout</span>
            </div>
            <div className="summary-row summary-total">
              <span>Totale</span>
              <strong>{formatPrice(total)}</strong>
            </div>
          </div>
          <button type="button" className="btn btn-primary btn-block btn-lg" onClick={handleCheckout}>
            Procedi all'ordine
          </button>
          <Link to="/" className="continue-link">
            <LuArrowLeft aria-hidden="true" /> Continua lo shopping
          </Link>
        </aside>
      </div>
    </div>
  )
}
