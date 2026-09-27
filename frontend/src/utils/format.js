const priceFormatter = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' })

export function formatPrice(value) {
  return priceFormatter.format(Number(value))
}

export function formatOrderNumber(id) {
  return `#${String(id).padStart(5, '0')}`
}

// Sotto questa soglia mostriamo "Ultimi N pezzi", sopra solo "Disponibile":
// il numero esatto del magazzino non viene esposto.
export const LOW_STOCK_THRESHOLD = 5

export function isLowStock(stock) {
  return stock > 0 && stock <= LOW_STOCK_THRESHOLD
}

export function lowStockLabel(stock) {
  return stock === 1 ? 'Ultimo pezzo' : `Ultimi ${stock} pezzi`
}

// Etichette mostrate al cliente: il pagamento avviene fuori dal sito, quindi un
// ordine appena confermato è "in attesa di pagamento" (nell'admin resta "Nuovo").
export const ORDER_STATUS_LABELS = {
  nuovo: 'In attesa di pagamento',
  in_lavorazione: 'In lavorazione',
  evaso: 'Evaso',
  annullato: 'Annullato',
}
