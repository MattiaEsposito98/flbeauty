import { Link, NavLink } from 'react-router-dom'
import { FaWhatsapp } from 'react-icons/fa6'
import { LuHeart, LuShoppingBag, LuUser } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import { useCart } from '../context/CartContext'
import { useWishlist } from '../context/WishlistContext'
import { WHATSAPP_DISPLAY, WHATSAPP_URL } from '../config/contacts'
import Logo from './Logo'

export default function Navbar() {
  const { user } = useAuth()
  const { count, openDrawer } = useCart()
  const { products: wishlist } = useWishlist()

  const firstName = user?.name?.split(' ')[0]
  const cartLabel = `Apri il carrello (${count} ${count === 1 ? 'articolo' : 'articoli'})`

  return (
    <>
      <div className="announcement-bar">
        <a href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer">
          <FaWhatsapp aria-hidden="true" />
          <span className="announcement-long">Hai bisogno di un consiglio? Scrivici su WhatsApp</span>
          <span className="announcement-short">Scrivici su WhatsApp</span>
          <strong>{WHATSAPP_DISPLAY}</strong>
        </a>
      </div>

      <header className="site-header">
        <div className="header-inner">
          <Logo />

          <nav className="header-nav" aria-label="Navigazione principale">
            <NavLink to="/" end>
              Catalogo
            </NavLink>
          </nav>

          <div className="header-actions">
            <Link to="/preferiti" className="icon-btn" aria-label="I tuoi preferiti" title="Preferiti">
              <LuHeart aria-hidden="true" />
              {user && wishlist.length > 0 && <span className="icon-badge">{wishlist.length}</span>}
            </Link>

            <Link
              to={user ? '/account' : '/login'}
              className="header-account"
              aria-label={user ? 'Il tuo account' : 'Accedi'}
            >
              <span className="icon-btn" aria-hidden="true">
                <LuUser />
              </span>
              <span className="header-account-label">{user ? `Ciao, ${firstName}` : 'Accedi'}</span>
            </Link>

            {!user && (
              <Link to="/register" className="btn btn-outline btn-sm header-register">
                Registrati
              </Link>
            )}

            <button type="button" className="icon-btn" onClick={openDrawer} aria-label={cartLabel} title="Carrello">
              <LuShoppingBag aria-hidden="true" />
              {count > 0 && <span className="icon-badge">{count}</span>}
            </button>
          </div>
        </div>
      </header>
    </>
  )
}
