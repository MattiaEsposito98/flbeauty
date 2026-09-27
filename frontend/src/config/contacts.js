const WHATSAPP_NUMBER = '393517459482'

export const WHATSAPP_DISPLAY = '351 745 9482'

export function whatsappUrl(text) {
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`
}

export const WHATSAPP_URL = whatsappUrl('Ciao F&L Beauty! Vorrei qualche informazione.')

export const EMAIL = 'flbeauty32@gmail.com'

export const TIKTOK_PROFILES = [
  { handle: 'flbeauty', url: 'https://www.tiktok.com/@flbeauty' },
  { handle: 'flbeauty2', url: 'https://www.tiktok.com/@flbeauty2' },
]
