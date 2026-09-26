import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import client from '../api/client'
import ProductCard from '../components/ProductCard'

export default function Catalog() {
  const [searchParams, setSearchParams] = useSearchParams()
  const activeCategory = searchParams.get('category') ?? ''
  const [categories, setCategories] = useState([])
  const [products, setProducts] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    client.get('/categories').then(({ data }) => setCategories(data.data))
  }, [])

  useEffect(() => {
    setLoading(true)
    client
      .get('/products', { params: activeCategory ? { category: activeCategory } : {} })
      .then(({ data }) => setProducts(data.data))
      .finally(() => setLoading(false))
  }, [activeCategory])

  return (
    <div className="page page-catalog">
      <h1>Catalogo</h1>

      <div className="category-filters">
        <button
          className={activeCategory === '' ? 'active' : ''}
          onClick={() => setSearchParams({})}
        >
          Tutte
        </button>
        {categories.map((category) => (
          <button
            key={category.id}
            className={activeCategory === category.slug ? 'active' : ''}
            onClick={() => setSearchParams({ category: category.slug })}
          >
            {category.name}
          </button>
        ))}
      </div>

      {loading ? (
        <p>Caricamento...</p>
      ) : products.length === 0 ? (
        <p>Nessun prodotto trovato.</p>
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
