import { formatOrderNumber, formatPrice } from './format'

export function orderAmounts(order) {
  const subtotal = order.items.reduce((sum, item) => sum + item.quantity * Number(item.unit_price), 0)
  const shipping = Number(order.shipping_cost ?? 0)
  const discount = Math.max(0, subtotal + shipping - Number(order.total))

  return { subtotal, shipping, discount, total: Number(order.total) }
}

// Ritiro a mano: il cliente passa a ritirare di persona, quindi non c'è un indirizzo di spedizione da usare.
export function isHandPickup(order) {
  return order.shipping_rate?.type === 'a_mano'
}

export function formatShippingAddress(order) {
  if (isHandPickup(order) || !order.shipping_address_line) return null

  return `${order.shipping_address_line}, ${order.shipping_postal_code} ${order.shipping_city} (${order.shipping_province})`
}

// Testo del riepilogo che il cliente può inviare su WhatsApp dopo l'ordine.
export function orderWhatsAppText(order, user) {
  const { subtotal, shipping, discount, total } = orderAmounts(order)
  const address = formatShippingAddress(order)

  const lines = [
    `Ciao F&L Beauty! Ho appena effettuato l'ordine ${formatOrderNumber(order.id)}.`,
    '',
    '*Prodotti*',
    ...order.items.map(
      (item) =>
        `- ${item.product?.name ?? 'Prodotto'}${item.variant_name ? ` – ${item.variant_name}` : ''} x${item.quantity}: ${formatPrice(item.quantity * Number(item.unit_price))}`
    ),
    '',
    `Subtotale: ${formatPrice(subtotal)}`,
  ]

  if (order.discount) lines.push(`Sconto (${order.discount.code}): -${formatPrice(discount)}`)

  lines.push(
    isHandPickup(order)
      ? 'Ritiro a mano: gratis'
      : `Spedizione${order.shipping_rate ? ` (${order.shipping_rate.name})` : ''}: ${formatPrice(shipping)}`,
    `*Totale: ${formatPrice(total)}*`,
    '',
    '*I miei dati*',
    `Nome: ${order.customer_name}`
  )

  if (isHandPickup(order)) lines.push('Ritiro: passo io a ritirare, ci mettiamo d\'accordo su giorno e orario.')
  if (address) lines.push(`Indirizzo: ${address}`)
  if (order.customer_phone) lines.push(`Telefono: ${order.customer_phone}`)
  lines.push(`Email: ${order.customer_email ?? user?.email ?? ''}`)

  return lines.join('\n')
}
