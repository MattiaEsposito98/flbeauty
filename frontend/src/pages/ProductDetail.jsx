import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { FaWhatsapp } from 'react-icons/fa6'
import {
  LuChevronRight,
  LuCircleAlert,
  LuCircleCheck,
  LuCircleX,
  LuSearchX,
  LuShoppingBag,
  LuSparkles,
  LuTruck,
} from 'react-icons/lu'
import client from '../api/client'
import { useCart } from '../context/CartContext'
import WishlistButton from '../components/WishlistButton'
import ProductImage from '../components/ProductImage'
import QuantityStepper from '../components/QuantityStepper'
import Spinner from '../components/Spinner'
import EmptyState from '../components/EmptyState'
import { WHATSAPP_URL } from '../config/contacts'
import { formatPrice, isLowStock, lowStockLabel } from '../utils/format'

export default function ProductDetail() {
  const { slug } = useParams()
  const { addItem, getQuantityInCart } = useCart()
  const [product, setProduct] = useState(null)
  const [notFound, setNotFound] = useState(false)
  const [quantity, setQuantity] = useState(1)
  const [activeImage, setActiveImage] = useState(0)

  useEffect(() => {
    setNotFound(false)
    setActiveImage(0)
    setQuantity(1)
    client
      .get(`/products/${slug}`)
      .then(({ data }) => setProduct(data.data))
      .catch(() => setNotFound(true))
  }, [slug])

  if (notFound) {
    return (
      <div className="page">
        <EmptyState
          icon={LuSearchX}
          title="Prodotto non disponibile"
          action={
            <Link to="/" className="btn btn-primary">
              Torna al catalogo
            </Link>
          }
        >
          Questo prodotto non è più disponibile o il link non è corretto.
        </EmptyState>
      </div>
    )
  }

  if (!product) return <Spinner />

  const images = product.images ?? []
  const quantityInCart = getQuantityInCart(product.id)
  const remaining = Math.max(0, product.stock - quantityInCart)

  async function handleAdd() {
    await addItem(product, Math.min(quantity, remaining))
    setQuantity(1)
  }

  return (
    <div className="page product-page">
      <nav className="breadcrumb" aria-label="Percorso">
        <Link to="/">Catalogo</Link>
        {product.category && (
          <>
            <LuChevronRight aria-hidden="true" />
            <Link to={`/?category=${product.category.slug}`}>{product.category.name}</Link>
          </>
        )}
        <LuChevronRight aria-hidden="true" />
        <span aria-current="page">{product.name}</span>
      </nav>

      <div className="product-layout">
        <div className="product-gallery">
          <div className="product-gallery-main">
            <ProductImage src={images[activeImage]} alt={product.name} />
          </div>

          {images.length > 1 && (
            <div className="product-gallery-thumbs">
              {images.map((src, index) => (
                <button
                  key={src}
                  type="button"
                  className={`product-thumb ${index === activeImage ? 'active' : ''}`}
                  onClick={() => setActiveImage(index)}
                  aria-label={`Mostra immagine ${index + 1}`}
                >
                  <ProductImage src={src} compact loading="lazy" />
                </button>
              ))}
            </div>
          )}
        </div>

        <div className="product-info">
          {product.category && <span className="eyebrow">{product.category.name}</span>}
          <h1>{product.name}</h1>
          <p className="product-price">{formatPrice(product.price)}</p>

          {product.description && <p className="product-description">{product.description}</p>}

          {product.in_stock ? (
            isLowStock(product.stock) ? (
              <p className="stock-status low-stock-status">
                <LuCircleAlert aria-hidden="true" /> {lowStockLabel(product.stock)}
              </p>
            ) : (
              <p className="stock-status in-stock">
                <LuCircleCheck aria-hidden="true" /> Disponibile
              </p>
            )
          ) : (
            <p className="stock-status out-of-stock">
              <LuCircleX aria-hidden="true" /> Prodotto esaurito
            </p>
          )}

          {quantityInCart > 0 && (
            <p className="in-cart-note">
              <LuShoppingBag aria-hidden="true" /> Già nel carrello: {quantityInCart}
            </p>
          )}

          {product.in_stock &&
            (remaining > 0 ? (
              <div className="purchase-row">
                <QuantityStepper value={Math.min(quantity, remaining)} max={remaining} onChange={setQuantity} />
                <button type="button" className="btn btn-primary btn-lg" onClick={handleAdd}>
                  <LuShoppingBag aria-hidden="true" />
                  Aggiungi al carrello
                </button>
              </div>
            ) : (
              <p className="hint max-reached">Hai già nel carrello tutti i pezzi disponibili.</p>
            ))}

          <WishlistButton product={product} withLabel />

          <ul className="trust-list">
            <li>
              <span className="icon-circle" aria-hidden="true">
                <LuTruck />
              </span>
              Spedizione calcolata in base alla tua regione
            </li>
            <li>
              <span className="icon-circle" aria-hidden="true">
                <FaWhatsapp />
              </span>
              <span>
                Consigli e assistenza su{' '}
                <a href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer">
                  WhatsApp
                </a>
              </span>
            </li>
            <li>
              <span className="icon-circle" aria-hidden="true">
                <LuSparkles />
              </span>
              Prodotti selezionati con cura
            </li>
          </ul>
        </div>
      </div>
    </div>
  )
}
