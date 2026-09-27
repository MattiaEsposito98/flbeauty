import { FaWhatsapp } from 'react-icons/fa6'
import { WHATSAPP_URL } from '../config/contacts'

export default function WhatsAppButton() {
  return (
    <a
      href={WHATSAPP_URL}
      className="whatsapp-fab"
      target="_blank"
      rel="noopener noreferrer"
      aria-label="Scrivici su WhatsApp"
    >
      <FaWhatsapp aria-hidden="true" />
      <span className="whatsapp-fab-label">Scrivici</span>
    </a>
  )
}
