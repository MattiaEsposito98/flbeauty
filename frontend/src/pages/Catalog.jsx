import { useEffect, useRef, useState } from 'react'
import { Navigate, useNavigate, useNavigationType, useParams, useSearchParams } from 'react-router-dom'
import { LuChevronDown, LuChevronLeft, LuChevronRight, LuSearch, LuSearchX, LuX } from 'react-icons/lu'
import PillScroller from '../components/PillScroller'
import client from '../api/client'
import ProductCard from '../components/ProductCard'
import Seo from '../components/Seo'
import Spinner from '../components/Spinner'
import EmptyState from '../components/EmptyState'
import OctagonOrnament from '../components/OctagonOrnament'
import { DEFAULT_DESCRIPTION } from '../config/site'
import { initialHomeSeed, refreshHomeSeed } from '../utils/homeSeed'

const PAGE_SIZE = 24

export default function Catalog() {
  const [searchParams, setSearchParams] = useSearchParams()
  const navigate = useNavigate()
  const navigationType = useNavigationType()
  // La categoria sta nell'URL pulito (/categoria/rossetti), così Google la indicizza
  // come pagina a sé. Il vecchio formato /?category=… viene rimandato al nuovo.
  const { slug: categorySlug } = useParams()
  const legacyCategory = searchParams.get('category')
  const activeCategory = categorySlug ?? ''
  const activeSearch = searchParams.get('q') ?? ''
  // La marca è un filtro dentro la categoria (?marca=slug): si vede solo se la categoria ne ha.
  const activeBrand = activeCategory ? (searchParams.get('marca') ?? '') : ''
  const activePage = Number(searchParams.get('page')) || 1
  const [searchInput, setSearchInput] = useState(activeSearch)
  const [categories, setCategories] = useState([])
  const [brands, setBrands] = useState([])
  // Ordine mescolato della home: cambia a ogni ricaricamento e quando si torna su «Tutte»,
  // resta uguale tra una pagina e l'altra e col tasto «indietro» (vedi utils/homeSeed.js).
  const [homeSeed, setHomeSeed] = useState(() => initialHomeSeed(navigationType))
  const previousCategory = useRef(activeCategory)
  const [products, setProducts] = useState([])
  const [meta, setMeta] = useState({ currentPage: 1, lastPage: 1, total: 0 })
  const [loading, setLoading] = useState(true)
  const toolbarRef = useRef(null)

  useEffect(() => {
    client.get('/categories').then(({ data }) => setCategories(data.data))
  }, [])

  useEffect(() => {
    setBrands([])
    if (!activeCategory) return
    client
      .get('/brands', { params: { category: activeCategory } })
      .then(({ data }) => setBrands(data.data))
      .catch(() => setBrands([]))
  }, [activeCategory])

  useEffect(() => {
    setLoading(true)
    const params = { page: activePage, per_page: PAGE_SIZE }
    if (activeCategory) params.category = activeCategory
    if (activeBrand) params.brand = activeBrand
    if (activeSearch) params.search = activeSearch
    // Home («Tutte», senza filtri): prodotti mescolati, con la cosmetica in evidenza (lato server).
    if (!activeCategory && !activeSearch && homeSeed) params.seed = homeSeed

    // Se i filtri cambiano mentre la richiesta è in corso, la risposta vecchia viene ignorata.
    let ignore = false

    client
      .get('/products', { params })
      .then(({ data }) => {
        if (ignore) return
        setProducts(data.data)
        setMeta({
          currentPage: data.meta.current_page,
          lastPage: data.meta.last_page,
          total: data.meta.total,
        })
      })
      .finally(() => {
        if (!ignore) setLoading(false)
      })

    return () => {
      ignore = true
    }
  }, [activeCategory, activeBrand, activeSearch, activePage, homeSeed])

  // Si torna su «Tutte» da una categoria (non con «indietro»): nuovo ordine.
  useEffect(() => {
    if (previousCategory.current !== '' && activeCategory === '' && navigationType !== 'POP') {
      setHomeSeed(refreshHomeSeed())
    }
    previousCategory.current = activeCategory
  }, [activeCategory, navigationType])

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

  function selectBrand(slug) {
    const next = new URLSearchParams(searchParams)
    if (slug) next.set('marca', slug)
    else next.delete('marca')
    next.delete('page')
    setSearchParams(next)
  }

  function selectCategory(slug) {
    // Premere «Tutte» mentre si è già su «Tutte» rimescola la home.
    if (!slug && activeCategory === '') setHomeSeed(refreshHomeSeed())
    const query = activeSearch ? `?q=${encodeURIComponent(activeSearch)}` : ''
    navigate(`${slug ? `/categoria/${slug}` : '/'}${query}`)
  }

  if (legacyCategory && !categorySlug) {
    return <Navigate to={`/categoria/${encodeURIComponent(legacyCategory)}`} replace />
  }

  const activeCategoryData = categories.find((category) => category.slug === activeCategory)
  const activeCategoryName = activeCategoryData?.name
  const activeBrandData = brands.find((brand) => brand.slug === activeBrand)
  const heading = activeSearch
    ? `Risultati per “${activeSearch}”`
    : (activeCategoryName ?? 'Tutti i prodotti')

  // SEO: ogni categoria è una pagina a sé. Le ricerche interne non vanno su Google
  // (infinite combinazioni, contenuto duplicato); le pagine successive alla prima sì,
  // ognuna col proprio indirizzo.
  const seoPath = activeCategory ? `/categoria/${activeCategory}` : '/'
  const seoPage = activePage > 1 ? `${seoPath}?page=${activePage}` : seoPath
  const seoTitle = activeSearch
    ? `Risultati per “${activeSearch}”`
    : activeCategoryName
      ? `${activeCategoryName}${activePage > 1 ? ` – pagina ${activePage}` : ''}`
      : activePage > 1
      ? `Tutti i prodotti – pagina ${activePage}`
      : undefined
  const seoDescription = activeCategoryName
    ? (activeCategoryData?.description ||
        `Scopri la categoria ${activeCategoryName} di F&L Beauty: prodotti beauty scelti con cura, con spedizione in tutta Italia.`)
    : DEFAULT_DESCRIPTION

  // Un solo h1 per pagina: sulla home è il titolo dell'hero, su categorie e ricerche
  // è il titolo della sezione (nome della categoria).
  const isFilteredView = Boolean(activeCategory || activeSearch)
  const HeroTitle = isFilteredView ? 'p' : 'h1'
  const SectionTitle = isFilteredView ? 'h1' : 'h2'

  return (
    <div className="page catalog-page">
      <Seo
        title={seoTitle}
        description={seoDescription}
        path={seoPage}
        noindex={Boolean(activeSearch) || Boolean(activeBrand) || (activeCategory !== '' && !loading && !activeCategoryName && categories.length > 0)}
      />
      <section className="hero">
        <div className="hero-content">
          <span className="eyebrow">Beauty shop</span>
          <HeroTitle className={isFilteredView ? 'hero-title' : undefined}>
            Il tuo momento di <em>bellezza</em>
          </HeroTitle>
          <p className="hero-text">
            Prodotti beauty scelti con cura, per prenderti cura di te ogni giorno.
          </p>
          <label className="search-field">
            <LuSearch aria-hidden="true" />
            <input
              type="search"
              placeholder="Cerca per nome, marca..."
              aria-label="Cerca per nome, marca o categoria"
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
          <SectionTitle>{heading}</SectionTitle>
          <div className="section-meta">
            {!loading && (
              <span className="results-count">
                {meta.total} {meta.total === 1 ? 'prodotto' : 'prodotti'}
              </span>
            )}
            {brands.length > 0 && (
              <label className="brand-select">
                <span>Marca</span>
                <select value={activeBrand} onChange={(e) => selectBrand(e.target.value)}>
                  <option value="">Tutte</option>
                  {brands.map((brand) => (
                    <option key={brand.id} value={brand.slug}>
                      {brand.name}
                    </option>
                  ))}
                </select>
                <LuChevronDown aria-hidden="true" />
              </label>
            )}
          </div>
        </div>

        <PillScroller label="Filtra per categoria" activeKey={activeCategory}>
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
        </PillScroller>

        {activeBrandData && (
          <div className="active-filters">
            <button type="button" className="filter-chip" onClick={() => selectBrand('')} aria-label={`Togli il filtro marca ${activeBrandData.name}`}>
              Marca: <strong>{activeBrandData.name}</strong>
              <LuX aria-hidden="true" />
            </button>
          </div>
        )}
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
