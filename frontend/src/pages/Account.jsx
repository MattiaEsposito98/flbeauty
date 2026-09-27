import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import {
  LuChevronRight,
  LuHeart,
  LuLogOut,
  LuMapPin,
  LuPackage,
  LuPencil,
  LuPlus,
  LuStar,
  LuTrash2,
} from 'react-icons/lu'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import AddressForm from '../components/AddressForm'
import EmptyState from '../components/EmptyState'
import Spinner from '../components/Spinner'
import { ORDER_STATUS_LABELS, formatOrderNumber, formatPrice } from '../utils/format'

function initialsOf(name = '') {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

export default function Account() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [addresses, setAddresses] = useState([])
  const [orders, setOrders] = useState([])
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState(null)
  const [loading, setLoading] = useState(true)

  async function loadAddresses() {
    const { data } = await client.get('/addresses')
    setAddresses(data)
    setLoading(false)
  }

  useEffect(() => {
    loadAddresses()
    client.get('/orders').then(({ data }) => setOrders(data))
  }, [])

  async function handleCreate(payload) {
    await client.post('/addresses', payload)
    setShowForm(false)
    await loadAddresses()
  }

  async function handleUpdate(payload) {
    await client.put(`/addresses/${editing.id}`, payload)
    setEditing(null)
    await loadAddresses()
  }

  async function handleDelete(address) {
    if (!confirm(`Eliminare l'indirizzo "${address.label || address.address_line}"?`)) return
    await client.delete(`/addresses/${address.id}`)
    await loadAddresses()
  }

  async function handleSetDefault(address) {
    await client.put(`/addresses/${address.id}`, { ...address, comune_id: address.comune.id, is_default: true })
    await loadAddresses()
  }

  async function handleLogout() {
    navigate('/')
    await logout()
  }

  if (loading) return <Spinner />

  return (
    <div className="page account-page">
      <section className="card account-hero">
        <span className="avatar" aria-hidden="true">
          {initialsOf(user?.name)}
        </span>
        <div className="account-hero-text">
          <span className="eyebrow">Il tuo account</span>
          <h1>Ciao, {user?.name?.split(' ')[0]}!</h1>
          <p className="muted">
            @{user?.username} · {user?.email}
          </p>
        </div>
        <div className="account-hero-actions">
          <Link to="/preferiti" className="btn btn-outline">
            <LuHeart aria-hidden="true" /> I miei preferiti
          </Link>
          <button type="button" className="btn btn-ghost" onClick={handleLogout}>
            <LuLogOut aria-hidden="true" /> Esci
          </button>
        </div>
      </section>

      <section className="card">
        <div className="card-header">
          <h2 className="card-title">
            <LuMapPin aria-hidden="true" /> I tuoi indirizzi
          </h2>
          {!showForm && (
            <button type="button" className="btn btn-outline btn-sm" onClick={() => setShowForm(true)}>
              <LuPlus aria-hidden="true" /> Aggiungi indirizzo
            </button>
          )}
        </div>

        {showForm && (
          <div className="address-form-panel">
            <h3>Nuovo indirizzo</h3>
            <AddressForm onSubmit={handleCreate} onCancel={() => setShowForm(false)} />
          </div>
        )}

        {addresses.length === 0 && !showForm && (
          <EmptyState icon={LuMapPin} title="Nessun indirizzo salvato">
            Aggiungi un indirizzo di spedizione per completare più velocemente i tuoi ordini.
          </EmptyState>
        )}

        <ul className="address-grid">
          {addresses.map((address) =>
            editing?.id === address.id ? (
              <li key={address.id} className="address-card address-card-editing">
                <h3>Modifica indirizzo</h3>
                <AddressForm initial={address} onSubmit={handleUpdate} onCancel={() => setEditing(null)} />
              </li>
            ) : (
              <li key={address.id} className={`address-card ${address.is_default ? 'is-default' : ''}`}>
                <div className="address-card-header">
                  <strong>{address.label || 'Indirizzo'}</strong>
                  {address.is_default && (
                    <span className="badge">
                      <LuStar aria-hidden="true" /> Principale
                    </span>
                  )}
                </div>
                <p className="address-lines">
                  {address.recipient_name}
                  <br />
                  {address.address_line}
                  <br />
                  {address.postal_code} {address.comune?.name} ({address.province})
                  {address.phone && (
                    <>
                      <br />
                      {address.phone}
                    </>
                  )}
                </p>
                <div className="address-actions">
                  <button type="button" className="btn btn-ghost btn-sm" onClick={() => setEditing(address)}>
                    <LuPencil aria-hidden="true" /> Modifica
                  </button>
                  {!address.is_default && (
                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => handleSetDefault(address)}>
                      <LuStar aria-hidden="true" /> Rendi principale
                    </button>
                  )}
                  <button
                    type="button"
                    className="btn btn-ghost btn-sm btn-ghost-danger"
                    onClick={() => handleDelete(address)}
                  >
                    <LuTrash2 aria-hidden="true" /> Elimina
                  </button>
                </div>
              </li>
            )
          )}
        </ul>
      </section>

      <section className="card">
        <div className="card-header">
          <h2 className="card-title">
            <LuPackage aria-hidden="true" /> I tuoi ordini
          </h2>
        </div>

        {orders.length === 0 ? (
          <EmptyState
            icon={LuPackage}
            title="Nessun ordine, per ora"
            action={
              <Link to="/" className="btn btn-primary">
                Scopri i prodotti
              </Link>
            }
          >
            Quando effettuerai il tuo primo ordine, lo troverai qui.
          </EmptyState>
        ) : (
          <ul className="order-list">
            {orders.map((order) => (
              <li key={order.id}>
                <Link to={`/ordini/${order.id}`} className="order-row">
                  <span className="icon-circle" aria-hidden="true">
                    <LuPackage />
                  </span>
                  <span className="order-row-main">
                    <span className="order-number">Ordine {formatOrderNumber(order.id)}</span>
                    <span className="order-date">
                      {new Date(order.created_at).toLocaleDateString('it-IT', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </span>
                  </span>
                  <span className={`badge status-badge status-${order.status}`}>
                    {ORDER_STATUS_LABELS[order.status] ?? order.status}
                  </span>
                  <span className="order-total">{formatPrice(order.total)}</span>
                  <LuChevronRight className="order-row-chevron" aria-hidden="true" />
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}
