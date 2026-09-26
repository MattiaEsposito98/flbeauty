import { Link } from 'react-router-dom'

export default function ProductCard({ product }) {
  return (
    <div className="product-card">
      <Link to={`/prodotti/${product.slug}`}>
        {product.images[0] ? (
          <img src={product.images[0]} alt={product.name} />
        ) : (
          <div className="product-card-placeholder">Nessuna immagine</div>
        )}
        <h3>{product.name}</h3>
      </Link>
      <p className="price">&euro;{product.price.toFixed(2)}</p>
      {!product.in_stock && <p className="out-of-stock">Esaurito</p>}
    </div>
  )
}
