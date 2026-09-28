import { useState } from 'react'
import { LuKeyRound } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import { apiError } from '../utils/apiError'
import Alert from './Alert'
import PasswordField from './PasswordField'

const emptyForm = { current_password: '', password: '', password_confirmation: '' }

// Dal profilo si cambia solo la password: nome utente ed email restano fissi.
export default function ChangePasswordCard() {
  const { changePassword } = useAuth()
  const [form, setForm] = useState(emptyForm)
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  function setField(field) {
    return (e) => setForm({ ...form, [field]: e.target.value })
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setErrors({})
    setMessage(null)
    setSubmitting(true)

    try {
      const { message } = await changePassword(form)
      setMessage(message)
      setForm(emptyForm)
    } catch (err) {
      const fieldErrors = err.response?.data?.errors
      setErrors(
        fieldErrors && !fieldErrors.form
          ? fieldErrors
          : { generic: [apiError(err, 'form', 'Non è stato possibile cambiare la password. Riprova.')] }
      )
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <section className="card">
      <div className="card-header">
        <h2 className="card-title">
          <LuKeyRound aria-hidden="true" /> Cambia password
        </h2>
      </div>

      <form onSubmit={handleSubmit}>
        <div className="form-grid">
          <div className="span-2">
            <PasswordField
              id="current-password"
              label="Password attuale"
              value={form.current_password}
              onChange={setField('current_password')}
              autoComplete="current-password"
              error={errors.current_password?.[0]}
              required
            />
          </div>
          <PasswordField
            id="new-password"
            label="Nuova password"
            value={form.password}
            onChange={setField('password')}
            autoComplete="new-password"
            minLength={8}
            hint="Almeno 8 caratteri."
            required
          />
          <PasswordField
            id="new-password-confirmation"
            label="Conferma nuova password"
            value={form.password_confirmation}
            onChange={setField('password_confirmation')}
            autoComplete="new-password"
            required
          />
          {errors.password && <p className="error span-2">{errors.password[0]}</p>}
        </div>

        {errors.generic && <Alert type="error">{errors.generic[0]}</Alert>}
        {message && <Alert type="success">{message}</Alert>}

        <div className="form-actions">
          <button type="submit" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Salvataggio...' : 'Aggiorna password'}
          </button>
        </div>
      </form>
    </section>
  )
}
