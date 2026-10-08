const WHATSAPP_NUMBER = '393517459482'

export const WHATSAPP_DISPLAY = '351 745 9482'

export function whatsappUrl(text) {
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`
}

export const WHATSAPP_URL = whatsappUrl('Ciao F&L Beauty! Vorrei qualche informazione.')

export const EMAIL = 'info@flbeauty.it'

export const TIKTOK_PROFILES = [
  { handle: 'fl.beauty', url: 'https://www.tiktok.com/@fl.beauty' },
  { handle: 'fl_beauty2', url: 'https://www.tiktok.com/@fl_beauty2' },
]
