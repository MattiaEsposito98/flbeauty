import { useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LuShoppingBag, LuTrash2, LuX } from 'react-icons/lu'
import { useCart } from '../context/CartContext'
import { useAuth } from '../context/AuthContext'
import { formatPrice } from '../utils/format'
import EmptyState from './EmptyState'
import ProductImage from './ProductImage'
import QuantityStepper from './QuantityStepper'

export default function CartDrawer() {
  const { items, updateQuantity, removeItem, total, count, drawerOpen, closeDrawer } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    if (!drawerOpen) return

    function handleKeyDown(e) {
      if (e.key === 'Escape') closeDrawer()
    }

    document.addEventListener('keydown', handleKeyDown)
    document.body.style.overflow = 'hidden'

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = ''
    }
  }, [drawerOpen])

  if (!drawerOpen) return null

  function goToCheckout() {
    closeDrawer()
    navigate(user ? '/checkout' : '/login?redirect=/checkout')
  }

  return (
    <div className="drawer-backdrop" onClick={closeDrawer}>
      <aside
        className="drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-drawer-title"
        onClick={(e) => e.stopPropagation()}
      >
        <header className="drawer-header">
          <div className="drawer-title">
            <LuShoppingBag aria-hidden="true" />
            <h2 id="cart-drawer-title">Il tuo carrello</h2>
            {count > 0 && <span className="count-pill">{count}</span>}
          </div>
          <button type="button" className="icon-btn" onClick={closeDrawer} aria-label="Chiudi il carrello">
            <LuX aria-hidden="true" />
          </button>
        </header>

        {items.length === 0 ? (
          <div className="drawer-body">
            <EmptyState
              icon={LuShoppingBag}
              title="Il tuo carrello è vuoto"
              action={
                <Link to="/" className="btn btn-primary" onClick={closeDrawer}>
                  Scopri i prodotti
                </Link>
              }
            >
              Aggiungi i prodotti che ami e li ritroverai qui.
            </EmptyState>
          </div>
        ) : (
          <>
            <div className="drawer-body">
              <ul className="drawer-items">
                {items.map((item) => (
                  <li key={item.product.id} className="drawer-item">
                    <Link to={`/prodotti/${item.product.slug}`} className="item-thumb" onClick={closeDrawer} tabIndex={-1}>
                      <ProductImage src={item.product.images?.[0]} compact />
                    </Link>
                    <div className="drawer-item-info">
                      <div className="drawer-item-top">
                        <Link to={`/prodotti/${item.product.slug}`} className="item-name" onClick={closeDrawer}>
                          {item.product.name}
                        </Link>
                        <button
                          type="button"
                          className="icon-btn remove-btn"
                          onClick={() => removeItem(item.product.id)}
                          aria-label={`Rimuovi ${item.product.name}`}
                        >
                          <LuTrash2 aria-hidden="true" />
                        </button>
                      </div>
                      <span className="item-unit-price">{formatPrice(item.product.price)} cad.</span>
                      <div className="drawer-item-controls">
                        <QuantityStepper
                          size="sm"
                          value={item.quantity}
                          max={item.product.stock}
                          onChange={(quantity) => updateQuantity(item.product.id, quantity)}
                        />
                        <span className="item-total">{formatPrice(item.product.price * item.quantity)}</span>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            </div>

            <footer className="drawer-footer">
              <div className="summary-row summary-total">
                <span>Subtotale</span>
                <strong>{formatPrice(total)}</strong>
              </div>
              <p className="hint">La spedizione viene calcolata al momento dell'ordine.</p>
              <button type="button" className="btn btn-primary btn-block btn-lg" onClick={goToCheckout}>
                Procedi all'ordine
              </button>
              <Link to="/carrello" className="btn btn-outline btn-block" onClick={closeDrawer}>
                Vedi il carrello
              </Link>
            </footer>
          </>
        )}
      </aside>
    </div>
  )
}
