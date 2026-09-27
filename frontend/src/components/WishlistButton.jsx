import { useNavigate } from 'react-router-dom'
import { LuHeart } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import { useWishlist } from '../context/WishlistContext'

export default function WishlistButton({ product, className = '', withLabel = false }) {
  const { user } = useAuth()
  const { isWishlisted, toggle } = useWishlist()
  const navigate = useNavigate()

  const active = Boolean(user) && isWishlisted(product.id)
  const label = active ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti'

  function handleClick(e) {
    e.preventDefault()
    e.stopPropagation()

    if (!user) {
      navigate(`/login?redirect=${encodeURIComponent(window.location.pathname)}`)
      return
    }

    toggle(product)
  }

  return (
    <button
      type="button"
      className={`wishlist-button ${withLabel ? 'wishlist-button-labeled' : ''} ${active ? 'active' : ''} ${className}`}
      onClick={handleClick}
      title={label}
      aria-label={label}
    >
      <LuHeart aria-hidden="true" />
      {withLabel && <span aria-hidden="true">{active ? 'Nei tuoi preferiti' : 'Aggiungi ai preferiti'}</span>}
    </button>
  )
}
