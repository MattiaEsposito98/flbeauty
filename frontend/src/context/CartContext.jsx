import { createContext, useContext, useEffect, useState } from 'react'

const CartContext = createContext(null)
const STORAGE_KEY = 'cart'

function loadCart() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

export function CartProvider({ children }) {
  const [items, setItems] = useState(loadCart)

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  }, [items])

  function addItem(product, quantity = 1) {
    setItems((prev) => {
      const existing = prev.find((item) => item.product.id === product.id)

      if (existing) {
        return prev.map((item) =>
          item.product.id === product.id
            ? { ...item, quantity: item.quantity + quantity }
            : item
        )
      }

      return [...prev, { product, quantity }]
    })
  }

  function updateQuantity(productId, quantity) {
    setItems((prev) =>
      quantity <= 0
        ? prev.filter((item) => item.product.id !== productId)
        : prev.map((item) => (item.product.id === productId ? { ...item, quantity } : item))
    )
  }

  function removeItem(productId) {
    setItems((prev) => prev.filter((item) => item.product.id !== productId))
  }

  function clearCart() {
    setItems([])
  }

  const total = items.reduce((sum, item) => sum + item.product.price * item.quantity, 0)
  const count = items.reduce((sum, item) => sum + item.quantity, 0)

  return (
    <CartContext.Provider
      value={{ items, addItem, updateQuantity, removeItem, clearCart, total, count }}
    >
      {children}
    </CartContext.Provider>
  )
}

export function useCart() {
  const context = useContext(CartContext)

  if (!context) {
    throw new Error('useCart deve essere usato dentro <CartProvider>')
  }

  return context
}
