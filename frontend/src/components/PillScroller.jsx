import { useCallback, useEffect, useRef, useState } from 'react'
import { LuChevronLeft, LuChevronRight } from 'react-icons/lu'

// Riga di pillole su una sola linea che scorre di lato, anche con tante voci. Su computer
// compaiono le frecce ‹ › e una sfumatura sul bordo quando ci sono altre voci nascoste;
// la pillola attiva viene portata in vista all'apertura.
export default function PillScroller({ label, activeKey, children }) {
  const ref = useRef(null)
  const [edges, setEdges] = useState({ left: false, right: false })

  const update = useCallback(() => {
    const el = ref.current
    if (!el) return
    setEdges({
      left: el.scrollLeft > 4,
      right: el.scrollLeft + el.clientWidth < el.scrollWidth - 4,
    })
  }, [])

  useEffect(() => {
    const el = ref.current
    if (!el) return
    update()
    el.addEventListener('scroll', update, { passive: true })
    window.addEventListener('resize', update)
    return () => {
      el.removeEventListener('scroll', update)
      window.removeEventListener('resize', update)
    }
  }, [update, children])

  // Solo scorrimento orizzontale della riga (scrollIntoView muoverebbe anche la pagina).
  useEffect(() => {
    const el = ref.current
    const active = el?.querySelector('.pill.active')
    if (!el || !active) return
    el.scrollLeft = active.offsetLeft - el.clientWidth / 2 + active.offsetWidth / 2
    update()
  }, [activeKey, children, update])

  function scrollBy(direction) {
    const el = ref.current
    el?.scrollBy({ left: direction * el.clientWidth * 0.7, behavior: 'smooth' })
  }

  return (
    <div className={`pill-scroller ${edges.left ? 'fade-left' : ''} ${edges.right ? 'fade-right' : ''}`}>
      {edges.left && (
        <button type="button" className="pill-arrow pill-arrow-left" onClick={() => scrollBy(-1)} aria-label="Mostra le voci precedenti">
          <LuChevronLeft aria-hidden="true" />
        </button>
      )}
      <div className="category-pills" role="group" aria-label={label} ref={ref}>
        {children}
      </div>
      {edges.right && (
        <button type="button" className="pill-arrow pill-arrow-right" onClick={() => scrollBy(1)} aria-label="Mostra altre voci">
          <LuChevronRight aria-hidden="true" />
        </button>
      )}
    </div>
  )
}
