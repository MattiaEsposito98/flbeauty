import { Link } from 'react-router-dom'
import { LuHeart } from 'react-icons/lu'
import { useWishlist } from '../context/WishlistContext'
import ProductCard from '../components/ProductCard'
import EmptyState from '../components/EmptyState'
import Spinner from '../components/Spinner'

export default function Wishlist() {
  const { products, loaded } = useWishlist()

  return (
    <div className="page">
      <header className="page-header">
        <span className="eyebrow">Wishlist</span>
        <h1>
          I tuoi <em>preferiti</em>
        </h1>
        {loaded && products.length > 0 && (
          <p className="page-subtitle">
            {products.length} {products.length === 1 ? 'prodotto salvato' : 'prodotti salvati'}
          </p>
        )}
      </header>

      {!loaded ? (
        <Spinner />
      ) : products.length === 0 ? (
        <EmptyState
          icon={LuHeart}
          title="Ancora nessun preferito"
          action={
            <Link to="/" className="btn btn-primary">
              Scopri i prodotti
            </Link>
          }
        >
          Aggiungi un cuore ai prodotti che ami per ritrovarli sempre qui.
        </EmptyState>
      ) : (
        <div className="product-grid">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      )}
    </div>
  )
}
