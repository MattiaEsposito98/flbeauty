import OctagonOrnament from './OctagonOrnament'

export default function AuthCard({ title, subtitle, wide = false, footer, children }) {
  return (
    <div className="page auth-page">
      <div className={`auth-card ${wide ? 'auth-card-wide' : ''}`}>
        <header className="auth-card-header">
          <div className="auth-emblem" aria-hidden="true">
            <OctagonOrnament className="auth-emblem-ornament" />
            <img src="/logo-mark-128.webp" alt="" width="56" height="56" />
          </div>
          <h1>{title}</h1>
          {subtitle && <p>{subtitle}</p>}
        </header>

        {children}

        {footer && <div className="auth-footer">{footer}</div>}
      </div>
    </div>
  )
}
