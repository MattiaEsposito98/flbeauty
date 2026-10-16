import { createContext, useContext, useEffect, useRef, useState } from 'react'
import client from '../api/client'
import { useAuth } from './AuthContext'
import { fromServerItems, itemName, itemPrice, itemStock, lineKey } from '../utils/cart'

const CartContext = createContext(null)
const STORAGE_KEY = 'cart'
const EDIT_KEY = 'cart-edit'

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

// Bozza dell'ordine in modifica, salvata nel browser: chiudendo la scheda non si perde.
function loadEditing() {
  try {
    const raw = localStorage.getItem(EDIT_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export function CartProvider({ children }) {
  const { user, loading: authLoading } = useAuth()
  const [items, setItems] = useState(loadCart)
  // Modifica di un ordine: { userId, orderId, order, items, reserved, mergeCart }. Quando c'è, il
  // carrello (cassetto, pagina, schede prodotto, checkout) mostra e modifica le righe dell'ORDINE;
  // il carrello vero resta intatto e si ritrova uscendo dalla modifica.
  const [editing, setEditing] = useState(loadEditing)
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

  // La bozza della modifica si ricorda nel browser.
  useEffect(() => {
    try {
      if (editing) localStorage.setItem(EDIT_KEY, JSON.stringify(editing))
      else localStorage.removeItem(EDIT_KEY)
    } catch {
      // localStorage non disponibile: la bozza vale finché la pagina resta aperta.
    }
  }, [editing])

  // Una bozza ritrovata all'apertura vale solo per lo stesso cliente e se l'ordine è ancora in attesa
  // di pagamento: se nel frattempo è stato pagato (o annullato) la modifica si chiude.
  useEffect(() => {
    if (authLoading || !editing) return

    if (!user || editing.userId !== user.id) {
      setEditing(null)
      return
    }

    client
      .get(`/orders/${editing.orderId}`)
      .then(({ data }) => {
        if (data.status !== 'nuovo') {
          setEditing(null)
          showToast("L'ordine non è più modificabile: la modifica è stata chiusa.", 'warning')
        }
      })
      .catch(() => setEditing(null))
  }, [authLoading, user?.id])

  // Le righe che il carrello mostra: quelle dell'ordine in modifica, oppure il carrello vero.
  const activeItems = editing ? editing.items : items

  // I pezzi che l'ordine in modifica già riserva si possono tenere anche se in magazzino non ci sono più.
  function reservedFor(productId, variantId) {
    return editing?.reserved?.[lineKey(productId, variantId)] ?? 0
  }

  function limitFor(item) {
    return itemStock(item) + reservedFor(item.product.id, item.variantId)
  }

  function updateDraft(updater) {
    setEditing((prev) => (prev ? { ...prev, items: updater(prev.items) } : prev))
  }

  // Con `variantId` indefinito conta tutte le varianti del prodotto (serve alla card del
  // catalogo: "nel carrello: N"); con una variante, solo quella riga.
  function getQuantityInCart(productId, variantId) {
    return activeItems
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

    const chosenId = variant?.id ?? null
    const stock = (variant ? variant.stock : product.stock) + reservedFor(product.id, chosenId)
    const name = variant ? `${product.name} – ${variant.name}` : product.name
    const inCart = getQuantityInCart(product.id, chosenId)
    const allowed = Math.min(quantity, stock - inCart)

    if (allowed <= 0) {
      warnLimit(name, stock)
      return 0
    }

    let limited = false

    if (editing) {
      const key = lineKey(product.id, chosenId)

      updateDraft((prev) =>
        prev.some((item) => lineKey(item.product.id, item.variantId) === key)
          ? prev.map((item) =>
              lineKey(item.product.id, item.variantId) === key ? { ...item, quantity: item.quantity + allowed } : item
            )
          : [...prev, { product, variantId: chosenId, quantity: allowed }]
      )
    } else if (user) {
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

    const target = editing ? "all'ordine" : 'al carrello'

    if (openDrawer) setDrawerOpen(true)
    else if (!limited) showToast(`${name} aggiunto ${target}`)

    return allowed
  }

  async function updateQuantity(productId, variantId, requested) {
    const key = lineKey(productId, variantId)
    const current = activeItems.find((item) => lineKey(item.product.id, item.variantId) === key)
    const stock = current ? limitFor(current) : undefined
    const quantity = stock != null ? Math.min(requested, stock) : requested

    if (quantity < requested && current) warnLimit(itemName(current), stock)

    if (editing) {
      updateDraft((prev) =>
        quantity <= 0
          ? prev.filter((item) => lineKey(item.product.id, item.variantId) !== key)
          : prev.map((item) => (lineKey(item.product.id, item.variantId) === key ? { ...item, quantity } : item))
      )
      return
    }

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
  // (esaurito, variante nascosta) rimosso, con un riepilogo delle modifiche. Nella modifica di
  // un ordine vale per le righe dell'ordine (contando i pezzi che l'ordine già riserva) e non
  // tocca il carrello vero.
  async function syncAvailability() {
    if (activeItems.length === 0) return []

    const ids = [...new Set(activeItems.map((item) => item.product.id))].join(',')
    const { data } = await client.get('/products/availability', { params: { ids } })
    const fresh = new Map(data.data.map((product) => [product.id, product]))

    const changes = []
    const nextItems = []

    for (const item of activeItems) {
      const product = fresh.get(item.product.id)
      const updated = product ? { product, variantId: item.variantId, quantity: item.quantity, unitPrice: item.unitPrice } : null
      const available = updated ? limitFor(updated) : 0
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
      if (quantity > 0) nextItems.push({ product, variantId: item.variantId, quantity, unitPrice: item.unitPrice })
    }

    if (editing) {
      updateDraft(() => nextItems)
    } else if (user) {
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
    const key = lineKey(productId, variantId)

    if (editing) {
      updateDraft((prev) => prev.filter((item) => lineKey(item.product.id, item.variantId) !== key))
      return
    }

    if (user) {
      const { data } = await client.delete(`/cart/${productId}`, { params: { product_variant_id: variantId } })
      setItems(fromServerItems(data.items))
      return
    }

    setItems((prev) => prev.filter((item) => lineKey(item.product.id, item.variantId) !== key))
  }

  // Svuota il carrello VERO (anche durante una modifica: la bozza dell'ordine non si tocca).
  async function clearRealCart() {
    if (user) {
      await client.delete('/cart')
    }

    setItems([])
    setAdjustments([])
  }

  async function clearCart() {
    if (editing) {
      updateDraft(() => [])
      setAdjustments([])
      return
    }

    await clearRealCart()
  }

  /**
   * Apre un ordine in attesa di pagamento come carrello, per modificarlo navigando nel sito.
   * `mode` decide cosa fare del carrello che il cliente ha già:
   *  - 'keep' (o carrello vuoto): resta da parte e si ritrova uscendo dalla modifica
   *  - 'add': i suoi prodotti si aggiungono all'ordine (il carrello si svuota solo a modifica salvata)
   *  - 'clear': il carrello attuale viene svuotato
   * @returns elenco dei prodotti dell'ordine che non sono più disponibili e non sono entrati nella bozza
   */
  async function startEditing(order, mode = 'keep') {
    const ids = [...new Set(order.items.map((item) => item.product_id))].join(',')
    const { data } = await client.get('/products/availability', { params: { ids } })
    const fresh = new Map(data.data.map((product) => [product.id, product]))

    const lines = []
    const reserved = {}
    const dropped = []

    for (const item of order.items) {
      const product = fresh.get(item.product_id)
      const variantId = item.product_variant_id ?? null
      const label = `${item.product?.name ?? 'Prodotto'}${item.variant_name ? ` – ${item.variant_name}` : ''}`

      if (!product || (variantId && !product.variants?.some((v) => v.id === variantId))) {
        dropped.push(label)
        continue
      }

      const key = lineKey(item.product_id, variantId)
      reserved[key] = (reserved[key] ?? 0) + item.quantity
      lines.push({ product, variantId, quantity: item.quantity, unitPrice: Number(item.unit_price) })
    }

    if (mode === 'add') {
      for (const cartItem of items) {
        const key = lineKey(cartItem.product.id, cartItem.variantId)
        const existing = lines.find((line) => lineKey(line.product.id, line.variantId) === key)
        const room = itemStock(cartItem) + (reserved[key] ?? 0) - (existing?.quantity ?? 0)
        const quantity = Math.min(cartItem.quantity, Math.max(0, room))

        if (quantity <= 0) continue

        if (existing) existing.quantity += quantity
        else lines.push({ product: cartItem.product, variantId: cartItem.variantId, quantity })
      }
    }

    if (mode === 'clear') await clearRealCart()

    setAdjustments([])
    setEditing({
      userId: user.id,
      orderId: order.id,
      order: {
        id: order.id,
        discount: order.discount ?? null,
        shipping_cost: order.shipping_cost,
        shipping_rate_id: order.shipping_rate_id ?? null,
        shipping_address_line: order.shipping_address_line,
        shipping_postal_code: order.shipping_postal_code,
      },
      items: lines,
      reserved,
      mergeCart: mode === 'add',
    })

    if (dropped.length > 0) {
      showToast(`Non più disponibili e tolti dall'ordine: ${dropped.join(', ')}.`, 'warning')
    }

    return dropped
  }

  // Esce dalla modifica senza salvare: la bozza si butta, il carrello vero è com'era.
  function stopEditing() {
    setEditing(null)
    setAdjustments([])
  }

  // Modifica salvata: se i prodotti del carrello erano entrati nell'ordine, il carrello si svuota.
  async function finishEditing() {
    const merge = editing?.mergeCart
    setEditing(null)
    setAdjustments([])

    if (merge) await clearRealCart()
  }

  const total = activeItems.reduce((sum, item) => sum + itemPrice(item) * item.quantity, 0)
  const count = activeItems.reduce((sum, item) => sum + item.quantity, 0)

  return (
    <CartContext.Provider
      value={{
        items: activeItems,
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
        // Modifica di un ordine come carrello
        editing,
        reservedFor,
        realCount: items.reduce((sum, item) => sum + item.quantity, 0),
        startEditing,
        stopEditing,
        finishEditing,
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
