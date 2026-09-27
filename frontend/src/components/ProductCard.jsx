import { Link } from 'react-router-dom'
import { LuShoppingBag } from 'react-icons/lu'
import { useCart } from '../context/CartContext'
import { formatPrice, isLowStock, lowStockLabel } from '../utils/format'
import ProductImage from './ProductImage'
import QuantityStepper from './QuantityStepper'
import WishlistButton from './WishlistButton'

export default function ProductCard({ product }) {
  const { addItem, updateQuantity, getQuantityInCart } = useCart()
  const quantityInCart = getQuantityInCart(product.id)

  return (
    <article className="product-card">
      <div className="product-card-media">
        <ProductImage src={product.images[0]} loading="lazy" decoding="async" />
        {!product.in_stock && <span className="product-card-flag">Esaurito</span>}
        <WishlistButton product={product} className="product-card-wishlist" />
      </div>

      <div className="product-card-body">
        {product.category && <span className="product-card-category">{product.category.name}</span>}
        <h3 className="product-card-title">
          <Link to={`/prodotti/${product.slug}`}>{product.name}</Link>
        </h3>
        {isLowStock(product.stock) && <span className="low-stock">{lowStockLabel(product.stock)}</span>}

        <div className="product-card-footer">
          <span className="price">{formatPrice(product.price)}</span>

          {product.in_stock &&
            (quantityInCart > 0 ? (
              <div className="product-card-action">
                <QuantityStepper
                  size="sm"
                  min={0}
                  value={quantityInCart}
                  max={product.stock}
                  onChange={(quantity) => updateQuantity(product.id, quantity)}
                />
              </div>
            ) : (
              <button
                type="button"
                className="btn btn-primary btn-sm product-card-action"
                onClick={() => addItem(product, 1, { openDrawer: false })}
                aria-label={`Aggiungi ${product.name} al carrello`}
              >
                <LuShoppingBag aria-hidden="true" />
                Aggiungi
              </button>
            ))}
        </div>
      </div>
    </article>
  )
}
