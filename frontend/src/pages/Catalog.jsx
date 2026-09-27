import { useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { LuChevronLeft, LuChevronRight, LuSearch, LuSearchX } from 'react-icons/lu'
import client from '../api/client'
import ProductCard from '../components/ProductCard'
import Spinner from '../components/Spinner'
import EmptyState from '../components/EmptyState'
import OctagonOrnament from '../components/OctagonOrnament'

export default function Catalog() {
  const [searchParams, setSearchParams] = useSearchParams()
  const activeCategory = searchParams.get('category') ?? ''
  const activeSearch = searchParams.get('q') ?? ''
  const activePage = Number(searchParams.get('page')) || 1
  const [searchInput, setSearchInput] = useState(activeSearch)
  const [categories, setCategories] = useState([])
  const [products, setProducts] = useState([])
  const [meta, setMeta] = useState({ currentPage: 1, lastPage: 1, total: 0 })
  const [loading, setLoading] = useState(true)
  const toolbarRef = useRef(null)

  useEffect(() => {
    client.get('/categories').then(({ data }) => setCategories(data.data))
  }, [])

  useEffect(() => {
    setLoading(true)
    const params = { page: activePage }
    if (activeCategory) params.category = activeCategory
    if (activeSearch) params.search = activeSearch

    client
      .get('/products', { params })
      .then(({ data }) => {
        setProducts(data.data)
        setMeta({
          currentPage: data.meta.current_page,
          lastPage: data.meta.last_page,
          total: data.meta.total,
        })
      })
      .finally(() => setLoading(false))
  }, [activeCategory, activeSearch, activePage])

  // Cambia pagina mantenendo gli altri filtri attivi (categoria, ricerca).
  function goToPage(page) {
    const next = new URLSearchParams(searchParams)
    if (page > 1) next.set('page', String(page))
    else next.delete('page')
    setSearchParams(next)
    toolbarRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  // Debounce: aggiorna l'URL (e quindi la ricerca) 400ms dopo che l'utente
  // smette di digitare, invece che ad ogni tasto premuto. Ogni nuova ricerca
  // riparte dalla prima pagina.
  useEffect(() => {
    const timeout = setTimeout(() => {
      if (searchInput === activeSearch) return

      const next = new URLSearchParams(searchParams)
      if (searchInput) next.set('q', searchInput)
      else next.delete('q')
      next.delete('page')
      setSearchParams(next, { replace: true })
    }, 400)

    return () => clearTimeout(timeout)
  }, [searchInput])

  function selectCategory(slug) {
    const next = {}
    if (slug) next.category = slug
    if (activeSearch) next.q = activeSearch
    setSearchParams(next)
  }

  const activeCategoryName = categories.find((category) => category.slug === activeCategory)?.name
  const heading = activeSearch
    ? `Risultati per “${activeSearch}”`
    : (activeCategoryName ?? 'Tutti i prodotti')

  return (
    <div className="page catalog-page">
      <section className="hero">
        <div className="hero-content">
          <span className="eyebrow">Beauty shop</span>
          <h1>
            Il tuo momento di <em>bellezza</em>
          </h1>
          <p className="hero-text">
            Prodotti beauty scelti con cura, per prenderti cura di te ogni giorno.
          </p>
          <label className="search-field">
            <LuSearch aria-hidden="true" />
            <input
              type="search"
              placeholder="Cerca un prodotto..."
              aria-label="Cerca un prodotto"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
            />
          </label>
        </div>

        <div className="hero-visual" aria-hidden="true">
          <OctagonOrnament className="hero-ornament" />
          <img src="/logo-mark-360.webp" alt="" className="hero-logo" width="220" height="220" />
        </div>
      </section>

      <div className="catalog-toolbar" ref={toolbarRef}>
        <div className="section-heading">
          <h2>{heading}</h2>
          {!loading && (
            <span className="results-count">
              {meta.total} {meta.total === 1 ? 'prodotto' : 'prodotti'}
            </span>
          )}
        </div>

        <div className="category-pills" role="group" aria-label="Filtra per categoria">
          <button
            type="button"
            className={`pill ${activeCategory === '' ? 'active' : ''}`}
            onClick={() => selectCategory('')}
          >
            Tutte
          </button>
          {categories.map((category) => (
            <button
              key={category.id}
              type="button"
              className={`pill ${activeCategory === category.slug ? 'active' : ''}`}
              onClick={() => selectCategory(category.slug)}
            >
              {category.name}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <Spinner label="Carichiamo i prodotti..." />
      ) : products.length === 0 ? (
        <EmptyState icon={LuSearchX} title="Nessun prodotto trovato">
          {activeSearch
            ? `Non abbiamo trovato prodotti per “${activeSearch}”. Prova con un'altra parola.`
            : 'Non ci sono ancora prodotti in questa categoria.'}
        </EmptyState>
      ) : (
        <>
          <div className="product-grid">
            {products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>

          {meta.lastPage > 1 && (
            <nav className="pagination" aria-label="Pagine del catalogo">
              <button
                type="button"
                className="btn btn-outline btn-sm"
                disabled={meta.currentPage <= 1}
                onClick={() => goToPage(meta.currentPage - 1)}
              >
                <LuChevronLeft aria-hidden="true" />
                Precedente
              </button>
              <span className="pagination-status">
                Pagina {meta.currentPage} di {meta.lastPage}
              </span>
              <button
                type="button"
                className="btn btn-outline btn-sm"
                disabled={meta.currentPage >= meta.lastPage}
                onClick={() => goToPage(meta.currentPage + 1)}
              >
                Successiva
                <LuChevronRight aria-hidden="true" />
              </button>
            </nav>
          )}
        </>
      )}
    </div>
  )
}
