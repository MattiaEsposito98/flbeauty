import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { LuMapPin, LuShieldCheck, LuUser } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import Alert from '../components/Alert'
import AuthCard from '../components/AuthCard'
import PasswordField from '../components/PasswordField'
import { useBotTrap } from '../components/BotTrap'
import { apiError } from '../utils/apiError'
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
  privacy_accepted: false,
  marketing_consent: false,
}

export default function Register() {
  const { register } = useAuth()
  const [form, setForm] = useState(initialForm)
  const [comune, setComune] = useState(null)
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)
  const [registered, setRegistered] = useState(false)
  const { trap, botFields } = useBotTrap()

  useEffect(() => {
    const options = comune?.postal_codes ?? []
    setForm((f) => ({ ...f, postal_code: options.length === 1 ? options[0] : '' }))
  }, [comune])

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  function handleCheckbox(e) {
    setForm({ ...form, [e.target.name]: e.target.checked })
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
        privacy_accepted: form.privacy_accepted,
        marketing_consent: form.marketing_consent,
        ...botFields(),
      })
      setRegistered(true)
    } catch (err) {
      // Errori sui campi se ci sono, altrimenti un errore generale (anti-bot,
      // troppi tentativi, errore imprevisto).
      const fieldErrors = err.response?.data?.errors
      setErrors(
        fieldErrors && !fieldErrors.form
          ? fieldErrors
          : { generic: [apiError(err, 'form', 'Errore durante la registrazione.')] }
      )
    } finally {
      setSubmitting(false)
    }
  }

  function fieldError(field) {
    return errors[field]?.[0]
  }

  if (registered) {
    return (
      <AuthCard title="Controlla la tua email" subtitle="Manca solo un ultimo passaggio!">
        <Alert type="success">
          Ti abbiamo inviato un'email all'indirizzo <strong>{form.email}</strong> con un link per
          verificare il tuo account. Aprilo per poter accedere.
        </Alert>
        <Link to="/login" className="btn btn-primary btn-block btn-lg">
          Vai al login
        </Link>
      </AuthCard>
    )
  }

  return (
    <AuthCard
      wide
      title="Crea il tuo account"
      subtitle="Registrati per ordinare i tuoi prodotti e salvare i tuoi preferiti."
      footer={
        <>
          Hai già un account? <Link to="/login">Accedi</Link>
        </>
      }
    >
      <form onSubmit={handleSubmit}>
        {trap}
        <section className="form-section">
          <h2 className="form-section-title">
            <LuUser aria-hidden="true" /> I tuoi dati
          </h2>
          <div className="form-grid">
            <div className="field">
              <label htmlFor="register-name">Nome e cognome *</label>
              <input
                id="register-name"
                name="name"
                autoComplete="name"
                value={form.name}
                onChange={handleChange}
                required
              />
            </div>
            <div className="field">
              <label htmlFor="register-username">Username *</label>
              <input
                id="register-username"
                name="username"
                autoComplete="username"
                value={form.username}
                onChange={handleChange}
                required
              />
              {fieldError('username') && <p className="error">{fieldError('username')}</p>}
            </div>
            <div className="field span-2">
              <label htmlFor="register-email">Email *</label>
              <input
                id="register-email"
                type="email"
                name="email"
                autoComplete="email"
                value={form.email}
                onChange={handleChange}
                required
              />
              {fieldError('email') && <p className="error">{fieldError('email')}</p>}
            </div>
            <PasswordField
              id="register-password"
              label="Password *"
              value={form.password}
              onChange={(e) => setForm({ ...form, password: e.target.value })}
              autoComplete="new-password"
              required
            />
            <PasswordField
              id="register-password-confirmation"
              label="Conferma password *"
              value={form.password_confirmation}
              onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
              autoComplete="new-password"
              required
            />
            {fieldError('password') && <p className="error span-2">{fieldError('password')}</p>}
          </div>
        </section>

        <section className="form-section">
          <h2 className="form-section-title">
            <LuMapPin aria-hidden="true" /> Indirizzo di spedizione
          </h2>
          <p className="hint">
            Questo sarà il tuo indirizzo principale per le spedizioni. Potrai aggiungerne altri in
            seguito dal tuo account.
          </p>

          <div className="form-grid">
            <div className="field">
              <label htmlFor="register-phone">Telefono *</label>
              <input
                id="register-phone"
                type="tel"
                name="phone"
                autoComplete="tel"
                value={form.phone}
                onChange={handleChange}
                required
              />
            </div>
            <div className="field">
              <label htmlFor="register-address">Via e civico *</label>
              <input
                id="register-address"
                name="address_line"
                autoComplete="street-address"
                value={form.address_line}
                onChange={handleChange}
                required
              />
            </div>

            <ComuneAutocomplete value={comune} onSelect={setComune} required />
            {fieldError('address.comune_id') && <p className="error span-2">Seleziona un comune valido.</p>}

            <PostalCodeField
              comune={comune}
              value={form.postal_code}
              onChange={(postal_code) => setForm({ ...form, postal_code })}
            />
            <div className="field">
              <label>Provincia</label>
              <input value={comune?.province ?? ''} disabled placeholder="Derivata dal comune" />
            </div>
            {fieldError('address.postal_code') && (
              <p className="error span-2">{fieldError('address.postal_code')}</p>
            )}
          </div>
        </section>

        <section className="form-section">
          <h2 className="form-section-title">
            <LuShieldCheck aria-hidden="true" /> Privacy
          </h2>
          <label className="checkbox consent-checkbox">
            <input
              type="checkbox"
              name="privacy_accepted"
              checked={form.privacy_accepted}
              onChange={handleCheckbox}
              required
            />
            <span>
              Dichiaro di aver compiuto 14 anni e di aver letto l'
              <Link to="/privacy" target="_blank">
                informativa privacy
              </Link>{' '}
              *
            </span>
          </label>
          {fieldError('privacy_accepted') && <p className="error">{fieldError('privacy_accepted')}</p>}
          <label className="checkbox consent-checkbox">
            <input
              type="checkbox"
              name="marketing_consent"
              checked={form.marketing_consent}
              onChange={handleCheckbox}
            />
            <span>
              Voglio ricevere via email offerte, sconti e novità di F&amp;L Beauty (facoltativo, puoi
              cambiare idea quando vuoi dal tuo account)
            </span>
          </label>
        </section>

        {errors.generic && <Alert type="error">{errors.generic[0]}</Alert>}

        <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
          {submitting ? 'Creazione account...' : 'Crea il mio account'}
        </button>
      </form>
    </AuthCard>
  )
}
