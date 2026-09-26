import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import AddressForm from '../components/AddressForm'

const STATUS_LABELS = {
  nuovo: 'Nuovo',
  in_lavorazione: 'In lavorazione',
  evaso: 'Evaso',
  annullato: 'Annullato',
}

export default function Account() {
  const { user } = useAuth()
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

  if (loading) return <p>Caricamento...</p>

  return (
    <div className="page page-account">
      <h1>Il tuo account</h1>
      <p>
        Ciao {user?.name} (@{user?.username})
      </p>

      <h2>I tuoi indirizzi</h2>

      <ul className="address-list">
        {addresses.map((address) => (
          <li key={address.id} className={address.is_default ? 'default' : ''}>
            {editing?.id === address.id ? (
              <AddressForm
                initial={address}
                onSubmit={handleUpdate}
                onCancel={() => setEditing(null)}
              />
            ) : (
              <>
                <strong>{address.label || 'Indirizzo'}</strong>
                {address.is_default && <span className="badge">Principale</span>}
                <p>
                  {address.recipient_name} — {address.address_line}, {address.postal_code}{' '}
                  {address.comune?.name} ({address.province})
                </p>
                <p>{address.phone}</p>
                <div className="actions">
                  <button onClick={() => setEditing(address)}>Modifica</button>
                  {!address.is_default && (
                    <button onClick={() => handleSetDefault(address)}>Imposta come principale</button>
                  )}
                  <button onClick={() => handleDelete(address)} className="danger">
                    Elimina
                  </button>
                </div>
              </>
            )}
          </li>
        ))}
      </ul>

      {showForm ? (
        <AddressForm onSubmit={handleCreate} onCancel={() => setShowForm(false)} />
      ) : (
        <button onClick={() => setShowForm(true)}>+ Aggiungi indirizzo</button>
      )}

      <h2>I tuoi ordini</h2>

      {orders.length === 0 ? (
        <p>Non hai ancora effettuato ordini.</p>
      ) : (
        <ul className="order-list">
          {orders.map((order) => (
            <li key={order.id}>
              <Link to={`/ordini/${order.id}`}>
                <span className="order-number">#{String(order.id).padStart(5, '0')}</span>
                <span className="badge">{STATUS_LABELS[order.status] ?? order.status}</span>
                <span className="order-date">
                  {new Date(order.created_at).toLocaleDateString('it-IT')}
                </span>
                <span className="order-total">&euro;{Number(order.total).toFixed(2)}</span>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
