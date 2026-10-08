import { useEffect, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
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
import Seo from '../components/Seo'
import Spinner from '../components/Spinner'
import EmptyState from '../components/EmptyState'
import { WHATSAPP_URL } from '../config/contacts'
import { SITE_NAME, SITE_URL } from '../config/site'
import { defaultVariant } from '../utils/cart'
import { formatPrice, isLowStock, lowStockLabel } from '../utils/format'

// Taglia il testo a una lunghezza adatta alla descrizione dei risultati di Google
// (circa 155 caratteri) senza spezzare l'ultima parola.
function truncate(text, max) {
  const clean = text.replace(/\s+/g, ' ').trim()
  if (clean.length <= max) return clean
  return `${clean.slice(0, max - 1).replace(/\s+\S*$/, '')}…`
}

export default function ProductDetail() {
  const { slug } = useParams()
  const [product, setProduct] = useState(null)
  const [notFound, setNotFound] = useState(false)

  useEffect(() => {
    setNotFound(false)
    setProduct(null)
    client
      .get(`/products/${slug}`)
      .then(({ data }) => setProduct(data.data))
      .catch(() => setNotFound(true))
  }, [slug])

  if (notFound) {
    return (
      <div className="page">
        <Seo title="Prodotto non disponibile" noindex />
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

  // `key`: cambiando prodotto la scheda riparte da zero (variante, quantità, foto).
  return <ProductView key={product.id} product={product} />
}

function ProductView({ product }) {
  const { addItem, getQuantityInCart } = useCart()
  const [searchParams, setSearchParams] = useSearchParams()
  const [quantity, setQuantity] = useState(1)
  const [activeImage, setActiveImage] = useState(0)

  const variants = product.variants ?? []

  // Parte la variante indicata nel link (?variante=ID) se esiste; altrimenti quella con più
  // disponibilità, così chi arriva trova subito qualcosa di ordinabile.
  const [variantId, setVariantId] = useState(() => {
    if (!product.has_variants) return null
    const fromLink = variants.find((variant) => String(variant.id) === searchParams.get('variante'))
    return (fromLink ?? defaultVariant(product)).id
  })

  const variant = variants.find((v) => v.id === variantId) ?? null

  function selectVariant(next) {
    setVariantId(next.id)
    setActiveImage(0)
    setQuantity(1)
    setSearchParams({ variante: String(next.id) }, { replace: true })
  }

  // Foto: se la variante ne ha una propria è la prima della galleria.
  const images = [variant?.image, ...(product.images ?? [])].filter(Boolean)
  const price = variant ? variant.price : product.price
  const stock = product.has_variants ? (variant?.stock ?? 0) : product.stock
  const inStock = stock > 0
  const quantityInCart = getQuantityInCart(product.id, variant?.id ?? null)
  const remaining = Math.max(0, stock - quantityInCart)

  async function handleAdd() {
    await addItem(product, Math.min(quantity, remaining), { variantId: variant?.id ?? null })
    setQuantity(1)
  }

  const productPath = `/prodotti/${product.slug}`
  const productUrl = `${SITE_URL}${productPath}`
  const seoDescription = product.description
    ? truncate(product.description, 155)
    : `${product.name} su F&L Beauty: prodotti beauty scelti con cura. Ordina online e ricevi a casa tua.`

  const seller = { '@type': 'Organization', name: SITE_NAME }
  const offer = (name, offerPrice, available) => ({
    '@type': 'Offer',
    ...(name && { name }),
    url: productUrl,
    priceCurrency: 'EUR',
    price: Number(offerPrice).toFixed(2),
    itemCondition: 'https://schema.org/NewCondition',
    availability: available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    seller,
  })

  // Con le varianti l'offerta è un intervallo di prezzo con una voce per variante.
  const prices = variants.map((v) => v.price)
  const offers = product.has_variants
    ? {
        '@type': 'AggregateOffer',
        priceCurrency: 'EUR',
        lowPrice: Math.min(...prices).toFixed(2),
        highPrice: Math.max(...prices).toFixed(2),
        offerCount: variants.length,
        availability: product.in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        offers: variants.map((v) => offer(v.name, v.price, v.in_stock)),
      }
    : offer(null, product.price, product.in_stock)

  // Dati strutturati: permettono a Google di mostrare prezzo e disponibilità
  // direttamente nei risultati di ricerca.
  const jsonLd = [
    {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: product.name,
      description: seoDescription,
      url: productUrl,
      ...((product.images ?? []).length > 0 && { image: product.images }),
      ...(product.category && { category: product.category.name }),
      brand: { '@type': 'Brand', name: SITE_NAME },
      offers,
    },
    {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Catalogo', item: SITE_URL },
        ...(product.category
          ? [
              {
                '@type': 'ListItem',
                position: 2,
                name: product.category.name,
                item: `${SITE_URL}/categoria/${product.category.slug}`,
              },
            ]
          : []),
        {
          '@type': 'ListItem',
          position: product.category ? 3 : 2,
          name: product.name,
          item: productUrl,
        },
      ],
    },
  ]

  return (
    <div className="page product-page">
      <Seo
        title={product.name}
        description={seoDescription}
        path={productPath}
        image={(product.images ?? [])[0]}
        type="product"
        jsonLd={jsonLd}
      />
      <nav className="breadcrumb" aria-label="Percorso">
        <Link to="/">Catalogo</Link>
        {product.category && (
          <>
            <LuChevronRight aria-hidden="true" />
            <Link to={`/categoria/${product.category.slug}`}>{product.category.name}</Link>
          </>
        )}
        <LuChevronRight aria-hidden="true" />
        <span aria-current="page">{product.name}</span>
      </nav>

      <div className="product-layout">
        <div className="product-gallery">
          <div className="product-gallery-main">
            <ProductImage src={images[activeImage]} alt={variant ? `${product.name} – ${variant.name}` : product.name} />
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
          <p className="product-price">{formatPrice(price)}</p>

          {product.description && <p className="product-description">{product.description}</p>}

          {product.has_variants && (
            <div className="variant-picker" role="radiogroup" aria-label={product.variant_label}>
              <p className="variant-picker-label">
                {product.variant_label}: <strong>{variant?.name}</strong>
              </p>
              <div className="variant-options">
                {variants.map((option) => (
                  <button
                    key={option.id}
                    type="button"
                    role="radio"
                    aria-checked={option.id === variantId}
                    disabled={!option.in_stock}
                    className={`variant-chip ${option.id === variantId ? 'active' : ''} ${option.in_stock ? '' : 'sold-out'}`}
                    onClick={() => selectVariant(option)}
                  >
                    {option.name}
                    {!option.in_stock && <span className="variant-chip-note">Esaurito</span>}
                  </button>
                ))}
              </div>
            </div>
          )}

          {inStock ? (
            isLowStock(stock) ? (
              <p className="stock-status low-stock-status">
                <LuCircleAlert aria-hidden="true" /> {lowStockLabel(stock)}
              </p>
            ) : (
              <p className="stock-status in-stock">
                <LuCircleCheck aria-hidden="true" /> Disponibile
              </p>
            )
          ) : (
            <p className="stock-status out-of-stock">
              <LuCircleX aria-hidden="true" /> {product.has_variants && !product.in_stock ? 'Prodotto esaurito' : 'Variante esaurita'}
            </p>
          )}

          {quantityInCart > 0 && (
            <p className="in-cart-note">
              <LuShoppingBag aria-hidden="true" /> Già nel carrello: {quantityInCart}
            </p>
          )}

          {inStock &&
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
