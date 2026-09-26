import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import ComuneAutocomplete from '../components/ComuneAutocomplete'
import PostalCodeField from '../components/PostalCodeField'

const initialForm = {
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  address_line: '',
  postal_code: '',
}

export default function Register() {
  const { register } = useAuth()
  const [form, setForm] = useState(initialForm)
  const [comune, setComune] = useState(null)
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)
  const [registered, setRegistered] = useState(false)

  useEffect(() => {
    const options = comune?.postal_codes ?? []
    setForm((f) => ({ ...f, postal_code: options.length === 1 ? options[0] : '' }))
  }, [comune])

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setErrors({})
    setSubmitting(true)

    try {
      await register({
        name: form.name,
        username: form.username,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
        address: {
          phone: form.phone,
          address_line: form.address_line,
          comune_id: comune?.id,
          postal_code: form.postal_code,
        },
      })
      setRegistered(true)
    } catch (err) {
      setErrors(err.response?.data?.errors ?? { generic: ['Errore durante la registrazione.'] })
    } finally {
      setSubmitting(false)
    }
  }

  function fieldError(field) {
    return errors[field]?.[0]
  }

  if (registered) {
    return (
      <div className="page page-register">
        <h1>Controlla la tua email</h1>
        <p>
          Ti abbiamo inviato un'email all'indirizzo <strong>{form.email}</strong> con un link
          per verificare il tuo account. Aprilo per poter accedere.
        </p>
        <p>
          <Link to="/login">Vai al login</Link>
        </p>
      </div>
    )
  }

  return (
    <div className="page page-register">
      <h1>Crea il tuo account</h1>
      <form onSubmit={handleSubmit}>
        <section>
          <h2>I tuoi dati</h2>
          <div className="field">
            <label>Nome e cognome *</label>
            <input name="name" value={form.name} onChange={handleChange} required />
          </div>
          <div className="field">
            <label>Username *</label>
            <input name="username" value={form.username} onChange={handleChange} required />
            {fieldError('username') && <p className="error">{fieldError('username')}</p>}
          </div>
          <div className="field">
            <label>Email *</label>
            <input type="email" name="email" value={form.email} onChange={handleChange} required />
            {fieldError('email') && <p className="error">{fieldError('email')}</p>}
          </div>
          <div className="field">
            <label>Password *</label>
            <input type="password" name="password" value={form.password} onChange={handleChange} required />
          </div>
          <div className="field">
            <label>Conferma password *</label>
            <input
              type="password"
              name="password_confirmation"
              value={form.password_confirmation}
              onChange={handleChange}
              required
            />
          </div>
        </section>

        <section>
          <h2>Indirizzo di spedizione</h2>
          <p className="hint">
            Questo sarà il tuo indirizzo principale per le spedizioni. Potrai aggiungerne altri
            in seguito dal tuo account.
          </p>

          <div className="field">
            <label>Telefono *</label>
            <input name="phone" value={form.phone} onChange={handleChange} required />
          </div>
          <div className="field">
            <label>Via e civico *</label>
            <input name="address_line" value={form.address_line} onChange={handleChange} required />
          </div>

          <ComuneAutocomplete value={comune} onSelect={setComune} required />
          {fieldError('address.comune_id') && <p className="error">Seleziona un comune valido.</p>}

          <div className="field-row">
            <PostalCodeField
              comune={comune}
              value={form.postal_code}
              onChange={(postal_code) => setForm({ ...form, postal_code })}
            />
            <div className="field">
              <label>Provincia</label>
              <input value={comune?.province ?? ''} disabled placeholder="Derivata dal comune" />
            </div>
          </div>
          {fieldError('address.postal_code') && <p className="error">{fieldError('address.postal_code')}</p>}
        </section>

        {errors.generic && <p className="error">{errors.generic[0]}</p>}

        <button type="submit" disabled={submitting}>
          {submitting ? 'Creazione account...' : 'Registrati'}
        </button>
      </form>
    </div>
  )
}
