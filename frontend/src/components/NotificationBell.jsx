import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { LuBell, LuGift, LuInfo, LuMegaphone, LuPackage } from 'react-icons/lu'
import client from '../api/client'

// Icona per tipo di notifica (order = ordini, welcome = codice di benvenuto, promo = offerte, service = avvisi).
const ICONS = { order: LuPackage, welcome: LuGift, promo: LuMegaphone, service: LuInfo }

const POLL_MS = 60_000

const relativeTime = new Intl.RelativeTimeFormat('it', { numeric: 'auto' })

function timeAgo(iso) {
  const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000)
  const steps = [
    [60, 'second'],
    [3600, 'minute'],
    [86400, 'hour'],
    [86400 * 30, 'day'],
  ]

  if (Math.abs(seconds) < 45) return 'adesso'

  for (const [limit, unit] of steps) {
    if (Math.abs(seconds) < limit) {
      const divisor = { second: 1, minute: 60, hour: 3600, day: 86400 }[unit]
      return relativeTime.format(Math.round(seconds / divisor), unit)
    }
  }

  return new Date(iso).toLocaleDateString('it-IT', { day: 'numeric', month: 'long' })
}

// Campanella nell'intestazione: le notifiche del sito (ogni email ai clienti ne ha una, così chi
// perde l'email la ritrova qui). Il numero delle non lette si aggiorna ogni minuto circa, quando
// si cambia pagina e quando si torna sulla scheda; l'elenco si carica solo all'apertura.
export default function NotificationBell() {
  const location = useLocation()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const [unread, setUnread] = useState(0)
  const [items, setItems] = useState(null)
  const rootRef = useRef(null)

  const refreshCount = useCallback(() => {
    if (document.visibilityState === 'hidden') return
    client
      .get('/notifications/unread-count')
      .then(({ data }) => setUnread(data.unread_count))
      .catch(() => {})
  }, [])

  const loadList = useCallback(() => {
    client
      .get('/notifications')
      .then(({ data }) => {
        setItems(data.data)
        setUnread(data.unread_count)
      })
      .catch(() => setItems((current) => current ?? []))
  }, [])

  useEffect(() => {
    refreshCount()
    const timer = setInterval(refreshCount, POLL_MS)
    window.addEventListener('focus', refreshCount)
    document.addEventListener('visibilitychange', refreshCount)
    // Chi fa un'azione che genera una notifica (modifica o annulla un ordine) lo segnala qui.
    window.addEventListener('notifications:refresh', refreshCount)

    return () => {
      clearInterval(timer)
      window.removeEventListener('focus', refreshCount)
      document.removeEventListener('visibilitychange', refreshCount)
      window.removeEventListener('notifications:refresh', refreshCount)
    }
  }, [refreshCount])

  // Cambiando pagina il pannello si chiude e il numero si riallinea (es. dopo aver fatto un ordine).
  useEffect(() => {
    setOpen(false)
    refreshCount()
  }, [location.pathname, refreshCount])

  useEffect(() => {
    if (!open) return

    loadList()

    function onPointerDown(e) {
      if (!rootRef.current?.contains(e.target)) setOpen(false)
    }

    function onKeyDown(e) {
      if (e.key === 'Escape') setOpen(false)
    }

    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)

    return () => {
      document.removeEventListener('pointerdown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open, loadList])

  async function markRead(item) {
    if (item.read) return

    setItems((current) => current?.map((n) => (n.id === item.id ? { ...n, read: true } : n)))
    setUnread((count) => Math.max(0, count - 1))
    client.post(`/notifications/${item.id}/read`).catch(() => {})
  }

  async function markAllRead() {
    setItems((current) => current?.map((n) => ({ ...n, read: true })))
    setUnread(0)
    client.post('/notifications/read-all').catch(() => {})
  }

  function openItem(item) {
    markRead(item)

    if (item.url) {
      setOpen(false)
      navigate(item.url)
    }
  }

  const label = unread > 0 ? `Notifiche (${unread} da leggere)` : 'Notifiche'

  return (
    <div className="notif" ref={rootRef}>
      <button
        type="button"
        className="icon-btn"
        onClick={() => setOpen((value) => !value)}
        aria-label={label}
        aria-haspopup="dialog"
        aria-expanded={open}
        title="Notifiche"
      >
        <LuBell aria-hidden="true" />
        {unread > 0 && <span className="icon-badge">{unread > 9 ? '9+' : unread}</span>}
      </button>

      {open && (
        <div className="notif-panel" role="dialog" aria-label="Notifiche">
          <div className="notif-header">
            <strong>Notifiche</strong>
            {unread > 0 && (
              <button type="button" className="link-button" onClick={markAllRead}>
                Segna tutte come lette
              </button>
            )}
          </div>

          {items === null ? (
            <p className="notif-empty">Caricamento...</p>
          ) : items.length === 0 ? (
            <p className="notif-empty">Nessuna notifica. Qui troverai gli avvisi su ordini, sconti e novità.</p>
          ) : (
            <ul className="notif-list">
              {items.map((item) => {
                const Icon = ICONS[item.kind] ?? LuInfo
                const content = (
                  <>
                    <span className={`notif-icon notif-icon-${item.kind}`} aria-hidden="true">
                      <Icon />
                    </span>
                    <span className="notif-text">
                      <strong>{item.title}</strong>
                      {item.body && <span className="notif-body">{item.body}</span>}
                      <span className="notif-time">{timeAgo(item.created_at)}</span>
                    </span>
                    {!item.read && <span className="notif-dot" aria-label="Da leggere" />}
                  </>
                )

                return (
                  <li key={item.id} className={item.read ? '' : 'is-unread'}>
                    {item.url ? (
                      <Link
                        to={item.url}
                        className="notif-item"
                        onClick={(e) => {
                          e.preventDefault()
                          openItem(item)
                        }}
                      >
                        {content}
                      </Link>
                    ) : (
                      <button type="button" className="notif-item" onClick={() => openItem(item)}>
                        {content}
                      </button>
                    )}
                  </li>
                )
              })}
            </ul>
          )}
        </div>
      )}
    </div>
  )
}
