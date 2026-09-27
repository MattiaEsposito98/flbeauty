import { Link } from 'react-router-dom'
import { FaTiktok, FaWhatsapp } from 'react-icons/fa6'
import { LuMail } from 'react-icons/lu'
import Logo from './Logo'
import { EMAIL, TIKTOK_PROFILES, WHATSAPP_DISPLAY, WHATSAPP_URL } from '../config/contacts'

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="footer-inner">
        <div className="footer-brand">
          <Logo className="logo-light" />
          <p>Prodotti beauty scelti con cura, per prenderti cura di te ogni giorno.</p>
        </div>

        <nav className="footer-col" aria-label="Esplora il sito">
          <h3>Esplora</h3>
          <ul>
            <li>
              <Link to="/">Catalogo</Link>
            </li>
            <li>
              <Link to="/preferiti">Preferiti</Link>
            </li>
            <li>
              <Link to="/carrello">Carrello</Link>
            </li>
            <li>
              <Link to="/account">Il mio account</Link>
            </li>
          </ul>
        </nav>

        <div className="footer-col">
          <h3>Contatti</h3>
          <ul>
            <li>
              <a href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer">
                <FaWhatsapp aria-hidden="true" />
                {WHATSAPP_DISPLAY}
              </a>
            </li>
            <li>
              <a href={`mailto:${EMAIL}`}>
                <LuMail aria-hidden="true" />
                {EMAIL}
              </a>
            </li>
          </ul>
        </div>

        <div className="footer-col">
          <h3>Seguici su TikTok</h3>
          <ul>
            {TIKTOK_PROFILES.map((profile) => (
              <li key={profile.handle}>
                <a href={profile.url} target="_blank" rel="noopener noreferrer">
                  <FaTiktok aria-hidden="true" />@{profile.handle}
                </a>
              </li>
            ))}
          </ul>
        </div>
      </div>

      <div className="footer-bottom">
        © {new Date().getFullYear()} F&amp;L Beauty. Tutti i diritti riservati.
      </div>
    </footer>
  )
}
