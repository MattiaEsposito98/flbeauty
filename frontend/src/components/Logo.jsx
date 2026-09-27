import { Link } from 'react-router-dom'

export default function Logo({ className = '' }) {
  return (
    <Link to="/" className={`logo ${className}`} aria-label="F&L Beauty, vai al catalogo">
      <img src="/logo-mark-128.webp" alt="" className="logo-mark" width="44" height="44" />
      <span className="logo-text" aria-hidden="true">
        <span className="logo-name">F&amp;L</span>
        <span className="logo-tagline">Beauty</span>
      </span>
    </Link>
  )
}
