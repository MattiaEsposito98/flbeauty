const priceFormatter = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' })

export function formatPrice(value) {
  return priceFormatter.format(Number(value))
}

export function formatOrderNumber(id) {
  return `#${String(id).padStart(5, '0')}`
}

export const ORDER_STATUS_LABELS = {
  nuovo: 'Nuovo',
  in_lavorazione: 'In lavorazione',
  evaso: 'Evaso',
  annullato: 'Annullato',
}
