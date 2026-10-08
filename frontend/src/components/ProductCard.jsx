import { Link } from 'react-router-dom'
import { LuShoppingBag, LuSwatchBook } from 'react-icons/lu'
import { useCart } from '../context/CartContext'
import { formatPrice, isLowStock, lowStockLabel } from '../utils/format'
import ProductImage from './ProductImage'
import QuantityStepper from './QuantityStepper'
import WishlistButton from './WishlistButton'

// Con le varianti il prezzo mostrato è il più basso tra quelle disponibili ("da ..." se differiscono).
function priceRange(product) {
  const prices = (product.variants ?? []).filter((variant) => variant.in_stock).map((variant) => variant.price)

  if (prices.length === 0) return { min: product.price, differs: false }

  const min = Math.min(...prices)
  return { min, differs: Math.max(...prices) > min }
}

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
          <span className="price">
            {product.has_variants && priceRange(product).differs && <span className="price-from">da </span>}
            {formatPrice(product.has_variants ? priceRange(product).min : product.price)}
          </span>

          {product.in_stock && product.has_variants && (
            <Link
              to={`/prodotti/${product.slug}`}
              className="btn btn-outline btn-sm product-card-action"
              aria-label={`Scegli ${product.variant_label?.toLowerCase() ?? 'variante'} di ${product.name}`}
            >
              <LuSwatchBook aria-hidden="true" />
              Scegli
            </Link>
          )}

          {product.in_stock &&
            !product.has_variants &&
            (quantityInCart > 0 ? (
              <div className="product-card-action">
                <QuantityStepper
                  size="sm"
                  min={0}
                  value={quantityInCart}
                  max={product.stock}
                  onChange={(quantity) => updateQuantity(product.id, null, quantity)}
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
