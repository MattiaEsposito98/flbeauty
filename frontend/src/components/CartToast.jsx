import { useEffect } from 'react'
import { LuCircleCheck, LuTriangleAlert, LuX } from 'react-icons/lu'
import { useCart } from '../context/CartContext'

export default function CartToast() {
  const { toast, dismissToast, openDrawer, drawerOpen } = useCart()

  useEffect(() => {
    if (!toast) return
    const timeout = setTimeout(dismissToast, toast.type === 'warning' ? 5000 : 3500)
    return () => clearTimeout(timeout)
  }, [toast?.id])

  if (!toast) return null

  const Icon = toast.type === 'warning' ? LuTriangleAlert : LuCircleCheck

  return (
    <div key={toast.id} className={`toast toast-${toast.type}`} role="status">
      <Icon className="toast-icon" aria-hidden="true" />
      <span className="toast-message">{toast.message}</span>
      {!drawerOpen && (
        <button
          type="button"
          className="toast-action"
          onClick={() => {
            dismissToast()
            openDrawer()
          }}
        >
          Vedi carrello
        </button>
      )}
      <button type="button" className="toast-close" onClick={dismissToast} aria-label="Chiudi avviso">
        <LuX aria-hidden="true" />
      </button>
    </div>
  )
}
