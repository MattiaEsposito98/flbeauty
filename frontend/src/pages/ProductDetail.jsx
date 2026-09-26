import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import client from '../api/client'
import { useCart } from '../context/CartContext'

export default function ProductDetail() {
  const { slug } = useParams()
  const navigate = useNavigate()
  const { addItem } = useCart()
  const [product, setProduct] = useState(null)
  const [quantity, setQuantity] = useState(1)
  const [added, setAdded] = useState(false)

  useEffect(() => {
    setAdded(false)
    client.get(`/products/${slug}`).then(({ data }) => setProduct(data.data))
  }, [slug])

  if (!product) return <p>Caricamento...</p>

  function handleAdd() {
    addItem(product, quantity)
    setAdded(true)
  }

  return (
    <div className="page page-product">
      {product.images[0] && <img src={product.images[0]} alt={product.name} className="product-image" />}
      <h1>{product.name}</h1>
      {product.category && <p className="category-tag">{product.category.name}</p>}
      <p className="price">&euro;{product.price.toFixed(2)}</p>
      <p>{product.description}</p>

      {product.in_stock ? (
        <>
          <div className="field-row">
            <div className="field">
              <label>Quantità</label>
              <input
                type="number"
                min="1"
                max={product.stock}
                value={quantity}
                onChange={(e) => setQuantity(Number(e.target.value))}
              />
            </div>
          </div>
          <button onClick={handleAdd}>Aggiungi al carrello</button>
          {added && (
            <p className="hint">
              Aggiunto al carrello. <a onClick={() => navigate('/carrello')}>Vai al carrello</a>
            </p>
          )}
        </>
      ) : (
        <p className="out-of-stock">Prodotto esaurito</p>
      )}
    </div>
  )
}
