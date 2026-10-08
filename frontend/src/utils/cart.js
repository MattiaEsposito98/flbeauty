// Una riga del carrello è { product, variantId, quantity }: il prodotto (con le sue varianti,
// come arriva dall'API) e, se il prodotto ha varianti, quella scelta. La disponibilità e il
// prezzo valgono per la variante; per i prodotti senza varianti per il prodotto.

export function lineKey(productId, variantId) {
  return `${productId}-${variantId ?? 0}`
}

export function itemVariant(item) {
  if (!item.variantId) return null
  return item.product.variants?.find((variant) => variant.id === item.variantId) ?? null
}

export function itemStock(item) {
  if (!item.variantId) return item.product.stock
  // Variante nascosta o eliminata nel frattempo: non è più ordinabile.
  return itemVariant(item)?.stock ?? 0
}

export function itemPrice(item) {
  return itemVariant(item)?.price ?? item.product.price
}

// "Rossetto matte – Rosso"
export function itemName(item) {
  const variant = itemVariant(item)
  return variant ? `${item.product.name} – ${variant.name}` : item.product.name
}

// Foto della variante se ce l'ha, altrimenti quella del prodotto.
export function itemImage(item) {
  return itemVariant(item)?.image ?? item.product.images?.[0]
}

// Righe come le restituisce il server ({ product, product_variant_id, quantity }).
export function fromServerItems(items) {
  return items.map((item) => ({
    product: item.product,
    variantId: item.product_variant_id ?? null,
    quantity: item.quantity,
  }))
}

// Variante da proporre per prima: quella con più disponibilità (a parità, la prima
// nell'ordine scelto dall'admin). Se sono tutte esaurite, la prima.
export function defaultVariant(product) {
  const variants = product.variants ?? []
  if (variants.length === 0) return null

  return variants.reduce((best, variant) => (variant.stock > best.stock ? variant : best), variants[0])
}
