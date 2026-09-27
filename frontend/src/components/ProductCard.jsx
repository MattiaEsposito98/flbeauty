import { Link } from 'react-router-dom'
import { LuShoppingBag } from 'react-icons/lu'
import { useCart } from '../context/CartContext'
import { formatPrice } from '../utils/format'
import ProductImage from './ProductImage'
import WishlistButton from './WishlistButton'

export default function ProductCard({ product }) {
  const { getQuantityInCart } = useCart()
  const quantityInCart = getQuantityInCart(product.id)

  return (
    <article className="product-card">
      <div className="product-card-media">
        <ProductImage src={product.images[0]} loading="lazy" decoding="async" />

        {!product.in_stock && <span className="product-card-flag">Esaurito</span>}
        {quantityInCart > 0 && (
          <span className="in-cart-badge product-card-in-cart">
            <LuShoppingBag aria-hidden="true" />
            Nel carrello ({quantityInCart})
          </span>
        )}
        <WishlistButton product={product} className="product-card-wishlist" />
      </div>

      <div className="product-card-body">
        {product.category && <span className="product-card-category">{product.category.name}</span>}
        <h3 className="product-card-title">
          <Link to={`/prodotti/${product.slug}`}>{product.name}</Link>
        </h3>
        <span className="price">{formatPrice(product.price)}</span>
      </div>
    </article>
  )
}
