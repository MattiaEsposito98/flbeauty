import { createContext, useContext, useEffect, useRef, useState } from 'react'
import client from '../api/client'
import { useAuth } from './AuthContext'
import { fromServerItems, itemName, itemPrice, itemStock, lineKey } from '../utils/cart'

const CartContext = createContext(null)
const STORAGE_KEY = 'cart'

// Carrello ospite salvato nel browser. Quelli salvati prima delle varianti non hanno
// `variantId`: valgono come righe senza variante.
function loadCart() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    const items = raw ? JSON.parse(raw) : []
    return items.map((item) => ({ ...item, variantId: item.variantId ?? null }))
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
          await client.post('/cart', {
            product_id: item.product.id,
            product_variant_id: item.variantId,
            quantity: item.quantity,
          })
        }

        if (guestItems.length > 0) {
          localStorage.removeItem(STORAGE_KEY)
        }

        const { data } = await client.get('/cart')
        setItems(fromServerItems(data.items))
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

  // Con `variantId` indefinito conta tutte le varianti del prodotto (serve alla card del
  // catalogo: "nel carrello: N"); con una variante, solo quella riga.
  function getQuantityInCart(productId, variantId) {
    return items
      .filter(
        (item) => item.product.id === productId && (variantId === undefined || item.variantId === (variantId ?? null))
      )
      .reduce((sum, item) => sum + item.quantity, 0)
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

  // Il server tronca le quantità alla disponibilità reale, che può essere più bassa di
  // quella letta dal client: se è successo, avvisiamo invece di tacere.
  function checkServerLimit(serverItems, productId, variantId, expected) {
    const saved = serverItems.find((item) => lineKey(item.product.id, item.variantId) === lineKey(productId, variantId))

    if ((saved?.quantity ?? 0) < expected) {
      warnLimit(saved ? itemName(saved) : 'il prodotto', saved ? itemStock(saved) : 0)
      return true
    }
    return false
  }

  // Aggiunge al massimo i pezzi ancora disponibili (disponibilità della variante, o del
  // prodotto se non ne ha, meno quelli già nel carrello). Dal catalogo si passa
  // openDrawer: false per non interrompere chi aggiunge più prodotti di fila.
  async function addItem(product, quantity = 1, { variantId = null, openDrawer = true } = {}) {
    const variant = variantId ? (product.variants ?? []).find((v) => v.id === variantId) : null

    if (product.has_variants && !variant) {
      showToast(`Scegli prima ${product.variant_label ? `il campo “${product.variant_label}”` : 'una variante'}`, 'warning')
      return 0
    }

    const stock = variant ? variant.stock : product.stock
    const name = variant ? `${product.name} – ${variant.name}` : product.name
    const chosenId = variant?.id ?? null
    const inCart = getQuantityInCart(product.id, chosenId)
    const allowed = Math.min(quantity, stock - inCart)

    if (allowed <= 0) {
      warnLimit(name, stock)
      return 0
    }

    let limited = false

    if (user) {
      const { data } = await client.post('/cart', {
        product_id: product.id,
        product_variant_id: chosenId,
        quantity: allowed,
      })
      const serverItems = fromServerItems(data.items)
      setItems(serverItems)
      limited = checkServerLimit(serverItems, product.id, chosenId, inCart + allowed)
    } else {
      setItems((prev) => {
        const key = lineKey(product.id, chosenId)
        const existing = prev.find((item) => lineKey(item.product.id, item.variantId) === key)

        if (existing) {
          return prev.map((item) =>
            lineKey(item.product.id, item.variantId) === key ? { ...item, quantity: item.quantity + allowed } : item
          )
        }

        return [...prev, { product, variantId: chosenId, quantity: allowed }]
      })
    }

    if (!limited && allowed < quantity) {
      warnLimit(name, stock)
      limited = true
    }

    if (openDrawer) setDrawerOpen(true)
    else if (!limited) showToast(`${name} aggiunto al carrello`)

    return allowed
  }

  async function updateQuantity(productId, variantId, requested) {
    const key = lineKey(productId, variantId)
    const current = items.find((item) => lineKey(item.product.id, item.variantId) === key)
    const stock = current ? itemStock(current) : undefined
    const quantity = stock != null ? Math.min(requested, stock) : requested

    if (quantity < requested && current) warnLimit(itemName(current), stock)

    if (user) {
      const { data } =
        quantity <= 0
          ? await client.delete(`/cart/${productId}`, { params: { product_variant_id: variantId } })
          : await client.patch(`/cart/${productId}`, { quantity, product_variant_id: variantId })
      const serverItems = fromServerItems(data.items)
      setItems(serverItems)
      if (quantity > 0 && quantity === requested) checkServerLimit(serverItems, productId, variantId, quantity)
      return
    }

    setItems((prev) =>
      quantity <= 0
        ? prev.filter((item) => lineKey(item.product.id, item.variantId) !== key)
        : prev.map((item) => (lineKey(item.product.id, item.variantId) === key ? { ...item, quantity } : item))
    )
  }

  // Ricontrolla disponibilità e prezzi nel carrello (all'apertura di carrello, pannello e
  // checkout): le quantità oltre la disponibilità vengono ridotte e quello che non c'è più
  // (esaurito, variante nascosta) rimosso, con un riepilogo delle modifiche.
  async function syncAvailability() {
    if (items.length === 0) return []

    const ids = [...new Set(items.map((item) => item.product.id))].join(',')
    const { data } = await client.get('/products/availability', { params: { ids } })
    const fresh = new Map(data.data.map((product) => [product.id, product]))

    const changes = []
    const nextItems = []

    for (const item of items) {
      const product = fresh.get(item.product.id)
      const updated = product ? { product, variantId: item.variantId, quantity: item.quantity } : null
      const available = updated ? itemStock(updated) : 0
      const quantity = Math.min(item.quantity, available)

      if (quantity < item.quantity) {
        changes.push({
          key: lineKey(item.product.id, item.variantId),
          productId: item.product.id,
          variantId: item.variantId,
          name: itemName(item),
          from: item.quantity,
          to: quantity,
        })
      }
      if (quantity > 0) nextItems.push({ product, variantId: item.variantId, quantity })
    }

    if (user) {
      for (const change of changes) {
        if (change.to > 0) {
          await client.patch(`/cart/${change.productId}`, { quantity: change.to, product_variant_id: change.variantId })
        } else {
          await client.delete(`/cart/${change.productId}`, { params: { product_variant_id: change.variantId } })
        }
      }
      const { data: cart } = await client.get('/cart')
      setItems(fromServerItems(cart.items))
    } else {
      setItems(nextItems)
    }

    if (changes.length > 0) setAdjustments(changes)
    return changes
  }

  async function removeItem(productId, variantId = null) {
    if (user) {
      const { data } = await client.delete(`/cart/${productId}`, { params: { product_variant_id: variantId } })
      setItems(fromServerItems(data.items))
      return
    }

    const key = lineKey(productId, variantId)
    setItems((prev) => prev.filter((item) => lineKey(item.product.id, item.variantId) !== key))
  }

  async function clearCart() {
    if (user) {
      await client.delete('/cart')
    }

    setItems([])
    setAdjustments([])
  }

  const total = items.reduce((sum, item) => sum + itemPrice(item) * item.quantity, 0)
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
