import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useCart } from '../context/CartContext'

export default function Navbar() {
  const { user, logout } = useAuth()
  const { count } = useCart()
  const navigate = useNavigate()

  async function handleLogout() {
    navigate('/')
    await logout()
  }

  return (
    <nav className="navbar">
      <Link to="/" className="brand">
        F&amp;L Beauty
      </Link>
      <div className="links">
        <Link to="/">Catalogo</Link>
        <Link to="/carrello">Carrello{count > 0 ? ` (${count})` : ''}</Link>
        {user ? (
          <>
            <Link to="/account">Il mio account</Link>
            <button onClick={handleLogout}>Esci</button>
          </>
        ) : (
          <>
            <Link to="/login">Accedi</Link>
            <Link to="/register">Registrati</Link>
          </>
        )}
      </div>
    </nav>
  )
}
