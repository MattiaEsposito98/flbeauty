import { useEffect } from 'react'
import { BrowserRouter, Link, Navigate, Route, Routes, useLocation } from 'react-router-dom'
import { LuSearchX } from 'react-icons/lu'
import { AuthProvider, useAuth } from './context/AuthContext'
import { CartProvider } from './context/CartContext'
import { WishlistProvider } from './context/WishlistContext'
import Navbar from './components/Navbar'
import CartDrawer from './components/CartDrawer'
import CartToast from './components/CartToast'
import Footer from './components/Footer'
import WhatsAppButton from './components/WhatsAppButton'
import EmptyState from './components/EmptyState'
import Seo from './components/Seo'
import Spinner from './components/Spinner'
import CookieBanner from './components/CookieBanner'
import Login from './pages/Login'
import Register from './pages/Register'
import ForgotPassword from './pages/ForgotPassword'
import ResetPassword from './pages/ResetPassword'
import Account from './pages/Account'
import Catalog from './pages/Catalog'
import ProductDetail from './pages/ProductDetail'
import Cart from './pages/Cart'
import Checkout from './pages/Checkout'
import OrderDetail from './pages/OrderDetail'
import Wishlist from './pages/Wishlist'
import Privacy from './pages/Privacy'
import CookiePolicy from './pages/CookiePolicy'
import Unsubscribe from './pages/Unsubscribe'

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <Spinner />
  if (!user) return <Navigate to={`/login?redirect=${encodeURIComponent(location.pathname)}`} replace />

  return children
}

// La SPA non resetta lo scroll tra una pagina e l'altra: senza questo,
// aprendo un prodotto dal fondo del catalogo si atterrerebbe a metà pagina.
function ScrollToTop() {
  const { pathname } = useLocation()

  useEffect(() => {
    window.scrollTo(0, 0)
  }, [pathname])

  return null
}

// Titolo e indicizzazione delle pagine che non gestiscono il SEO da sole.
// Account, carrello, ordini e accessi sono privati o senza contenuto utile:
// `noindex` li tiene fuori da Google (restano raggiungibili dal sito).
// Catalogo e prodotti usano <Seo> dentro la propria pagina.
const STATIC_SEO = {
  '/privacy': { title: 'Informativa privacy', description: 'Come F&L Beauty tratta i tuoi dati personali.' },
  '/cookie': { title: 'Cookie policy', description: 'Quali cookie usa il sito F&L Beauty e come gestirli.' },
  '/login': { title: 'Accedi', noindex: true },
  '/register': { title: 'Registrati', noindex: true },
  '/password-dimenticata': { title: 'Password dimenticata', noindex: true },
  '/reimposta-password': { title: 'Reimposta password', noindex: true },
  '/disiscrizione': { title: 'Disiscrizione dalle offerte', noindex: true },
  '/carrello': { title: 'Il tuo carrello', noindex: true },
  '/account': { title: 'Il tuo account', noindex: true },
  '/preferiti': { title: 'I tuoi preferiti', noindex: true },
  '/checkout': { title: 'Completa il tuo ordine', noindex: true },
}

// Indirizzo inesistente: il sito risponde sempre 200 (è una SPA), quindi la pagina
// va segnata `noindex` perché Google non la consideri un contenuto vero.
function NotFound() {
  return (
    <div className="page">
      <Seo title="Pagina non trovata" noindex />
      <EmptyState
        icon={LuSearchX}
        title="Pagina non trovata"
        action={
          <Link to="/" className="btn btn-primary">
            Torna al catalogo
          </Link>
        }
      >
        Il link che hai seguito non esiste più o non è corretto.
      </EmptyState>
    </div>
  )
}

function StaticSeo() {
  const { pathname } = useLocation()
  const entry = pathname.startsWith('/ordini/')
    ? { title: 'Il tuo ordine', noindex: true }
    : STATIC_SEO[pathname]

  if (!entry) return null

  return <Seo title={entry.title} description={entry.description} noindex={entry.noindex} />
}

function AppRoutes() {
  return (
    <>
      <StaticSeo />
      <ScrollToTop />
      <Navbar />
      <CartDrawer />
      <main>
        <Routes>
          <Route path="/" element={<Catalog />} />
          <Route path="/categoria/:slug" element={<Catalog />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/password-dimenticata" element={<ForgotPassword />} />
          <Route path="/reimposta-password" element={<ResetPassword />} />
          <Route path="/catalogo" element={<Navigate to="/" replace />} />
          <Route path="/prodotti/:slug" element={<ProductDetail />} />
          <Route path="/carrello" element={<Cart />} />
          <Route path="/privacy" element={<Privacy />} />
          <Route path="/cookie" element={<CookiePolicy />} />
          <Route path="/disiscrizione" element={<Unsubscribe />} />
          <Route
            path="/account"
            element={
              <ProtectedRoute>
                <Account />
              </ProtectedRoute>
            }
          />
          <Route
            path="/preferiti"
            element={
              <ProtectedRoute>
                <Wishlist />
              </ProtectedRoute>
            }
          />
          <Route
            path="/checkout"
            element={
              <ProtectedRoute>
                <Checkout />
              </ProtectedRoute>
            }
          />
          <Route
            path="/ordini/:id"
            element={
              <ProtectedRoute>
                <OrderDetail />
              </ProtectedRoute>
            }
          />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </main>
      <Footer />
      <WhatsAppButton />
      <CartToast />
      <CookieBanner />
    </>
  )
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <CartProvider>
          <WishlistProvider>
            <AppRoutes />
          </WishlistProvider>
        </CartProvider>
      </AuthProvider>
    </BrowserRouter>
  )
}
