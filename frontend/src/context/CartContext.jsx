import { createContext, useContext, useEffect, useRef, useState } from 'react'
import client from '../api/client'
import { useAuth } from './AuthContext'

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
  const { user, loading: authLoading } = useAuth()
  const [items, setItems] = useState(loadCart)
  const [drawerOpen, setDrawerOpen] = useState(false)
  const [loadedForUserId, setLoadedForUserId] = useState(null)
  const syncedUserId = useRef(null)

  // Il carrello degli ospiti vive in localStorage. Per gli utenti loggati vive
  // sul server (persiste tra dispositivi, come i preferiti): al login, il
  // carrello "ospite" eventualmente presente viene unito (sommato) a quello
  // salvato sull'account, poi lo stato locale riflette sempre il server.
  useEffect(() => {
    if (!user) {
      syncedUserId.current = null
      setLoadedForUserId(null)
      setItems(loadCart())
      return
    }

    if (syncedUserId.current === user.id) return
    syncedUserId.current = user.id

    ;(async () => {
      try {
        const guestItems = loadCart()

        for (const item of guestItems) {
          await client.post('/cart', { product_id: item.product.id, quantity: item.quantity })
        }

        if (guestItems.length > 0) {
          localStorage.removeItem(STORAGE_KEY)
        }

        const { data } = await client.get('/cart')
        setItems(data.items)
      } finally {
        setLoadedForUserId(user.id)
      }
    })()
  }, [user])

  // Finché il carrello dell'account non è arrivato dal server, le pagine
  // mostrano un caricamento invece di un falso "carrello vuoto".
  const loading = authLoading || (Boolean(user) && loadedForUserId !== user.id)

  // Solo il carrello degli ospiti va cache-ato in localStorage: quello degli
  // utenti loggati vive sul server, non va scritto qui (altrimenti al logout
  // riapparirebbe come se fosse un carrello "ospite").
  useEffect(() => {
    if (!user) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
    }
  }, [items, user])

  async function addItem(product, quantity = 1) {
    if (user) {
      const { data } = await client.post('/cart', { product_id: product.id, quantity })
      setItems(data.items)
    } else {
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

    setDrawerOpen(true)
  }

  function getQuantityInCart(productId) {
    return items.find((item) => item.product.id === productId)?.quantity ?? 0
  }

  async function updateQuantity(productId, quantity) {
    if (user) {
      const { data } =
        quantity <= 0
          ? await client.delete(`/cart/${productId}`)
          : await client.patch(`/cart/${productId}`, { quantity })
      setItems(data.items)
      return
    }

    setItems((prev) =>
      quantity <= 0
        ? prev.filter((item) => item.product.id !== productId)
        : prev.map((item) => (item.product.id === productId ? { ...item, quantity } : item))
    )
  }

  async function removeItem(productId) {
    if (user) {
      const { data } = await client.delete(`/cart/${productId}`)
      setItems(data.items)
      return
    }

    setItems((prev) => prev.filter((item) => item.product.id !== productId))
  }

  async function clearCart() {
    if (user) {
      await client.delete('/cart')
    }

    setItems([])
  }

  const total = items.reduce((sum, item) => sum + item.product.price * item.quantity, 0)
  const count = items.reduce((sum, item) => sum + item.quantity, 0)

  return (
    <CartContext.Provider
      value={{
        items,
        loading,
        addItem,
        updateQuantity,
        removeItem,
        clearCart,
        total,
        count,
        getQuantityInCart,
        drawerOpen,
        openDrawer: () => setDrawerOpen(true),
        closeDrawer: () => setDrawerOpen(false),
      }}
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
