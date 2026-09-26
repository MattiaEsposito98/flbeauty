import { Link, useNavigate } from 'react-router-dom'
import { useCart } from '../context/CartContext'
import { useAuth } from '../context/AuthContext'

export default function Cart() {
  const { items, updateQuantity, removeItem, total } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()

  if (items.length === 0) {
    return (
      <div className="page page-cart">
        <h1>Il tuo carrello</h1>
        <p>
          Il carrello è vuoto. <Link to="/catalogo">Vai al catalogo</Link>
        </p>
      </div>
    )
  }

  function handleCheckout() {
    navigate(user ? '/checkout' : '/login?redirect=/checkout')
  }

  return (
    <div className="page page-cart">
      <h1>Il tuo carrello</h1>

      <ul className="cart-list">
        {items.map((item) => (
          <li key={item.product.id}>
            <span className="cart-name">{item.product.name}</span>
            <input
              type="number"
              min="1"
              max={item.product.stock}
              value={item.quantity}
              onChange={(e) => updateQuantity(item.product.id, Number(e.target.value))}
            />
            <span className="cart-price">&euro;{(item.product.price * item.quantity).toFixed(2)}</span>
            <button onClick={() => removeItem(item.product.id)} className="danger">
              Rimuovi
            </button>
          </li>
        ))}
      </ul>

      <p className="cart-total">Totale: &euro;{total.toFixed(2)}</p>

      <button onClick={handleCheckout}>Procedi all'ordine</button>
    </div>
  )
}
