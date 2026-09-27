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
  const [toast, setToast] = useState(null)
  const [adjustments, setAdjustments] = useState([])
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

  function getQuantityInCart(productId) {
    return items.find((item) => item.product.id === productId)?.quantity ?? 0
  }

  function showToast(message, type = 'success') {
    setToast({ id: Date.now(), message, type })
  }

  function warnLimit(name, stock) {
    showToast(
      stock > 0 ? `Disponibili solo ${stock} ${stock === 1 ? 'pezzo' : 'pezzi'} di ${name}` : `${name} è esaurito`,
      'warning'
    )
  }

  // Il server tronca le quantità allo stock reale, che può essere più basso di
  // quello letto dal client: se è successo, avvisiamo invece di tacere.
  function checkServerLimit(serverItems, productId, expected) {
    const saved = serverItems.find((item) => item.product.id === productId)
    if ((saved?.quantity ?? 0) < expected) {
      warnLimit(saved?.product.name ?? 'il prodotto', saved?.product.stock ?? 0)
      return true
    }
    return false
  }

  // Aggiunge al massimo i pezzi ancora disponibili (stock meno quelli già nel
  // carrello). Dal catalogo si passa openDrawer: false per non interrompere
  // chi aggiunge più prodotti di fila: al posto del pannello compare un avviso.
  async function addItem(product, quantity = 1, { openDrawer = true } = {}) {
    const inCart = getQuantityInCart(product.id)
    const allowed = Math.min(quantity, product.stock - inCart)

    if (allowed <= 0) {
      warnLimit(product.name, product.stock)
      return 0
    }

    let limited = false

    if (user) {
      const { data } = await client.post('/cart', { product_id: product.id, quantity: allowed })
      setItems(data.items)
      limited = checkServerLimit(data.items, product.id, inCart + allowed)
    } else {
      setItems((prev) => {
        const existing = prev.find((item) => item.product.id === product.id)

        if (existing) {
          return prev.map((item) =>
            item.product.id === product.id
              ? { ...item, quantity: item.quantity + allowed }
              : item
          )
        }

        return [...prev, { product, quantity: allowed }]
      })
    }

    if (!limited && allowed < quantity) {
      warnLimit(product.name, product.stock)
      limited = true
    }

    if (openDrawer) setDrawerOpen(true)
    else if (!limited) showToast(`${product.name} aggiunto al carrello`)

    return allowed
  }

  async function updateQuantity(productId, requested) {
    const current = items.find((item) => item.product.id === productId)
    const stock = current?.product.stock
    const quantity = stock != null ? Math.min(requested, stock) : requested

    if (quantity < requested && current) warnLimit(current.product.name, stock)

    if (user) {
      const { data } =
        quantity <= 0
          ? await client.delete(`/cart/${productId}`)
          : await client.patch(`/cart/${productId}`, { quantity })
      setItems(data.items)
      if (quantity > 0 && quantity === requested) checkServerLimit(data.items, productId, quantity)
      return
    }

    setItems((prev) =>
      quantity <= 0
        ? prev.filter((item) => item.product.id !== productId)
        : prev.map((item) => (item.product.id === productId ? { ...item, quantity } : item))
    )
  }

  // Ricontrolla stock e prezzi dei prodotti nel carrello (all'apertura di
  // carrello, pannello e checkout): le quantità oltre la disponibilità vengono
  // ridotte e i prodotti esauriti rimossi, con un riepilogo delle modifiche.
  async function syncAvailability() {
    if (items.length === 0) return []

    const ids = items.map((item) => item.product.id).join(',')
    const { data } = await client.get('/products/availability', { params: { ids } })
    const fresh = new Map(data.data.map((product) => [product.id, product]))

    const changes = []
    const nextItems = []

    for (const item of items) {
      const product = fresh.get(item.product.id)
      const available = product?.stock ?? 0
      const quantity = Math.min(item.quantity, available)

      if (quantity < item.quantity) {
        changes.push({ productId: item.product.id, name: item.product.name, from: item.quantity, to: quantity })
      }
      if (quantity > 0) nextItems.push({ product, quantity })
    }

    if (user) {
      for (const change of changes) {
        if (change.to > 0) await client.patch(`/cart/${change.productId}`, { quantity: change.to })
        else await client.delete(`/cart/${change.productId}`)
      }
      const { data: cart } = await client.get('/cart')
      setItems(cart.items)
    } else {
      setItems(nextItems)
    }

    if (changes.length > 0) setAdjustments(changes)
    return changes
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
    setAdjustments([])
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
        toast,
        dismissToast: () => setToast(null),
        syncAvailability,
        adjustments,
        dismissAdjustments: () => setAdjustments([]),
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
