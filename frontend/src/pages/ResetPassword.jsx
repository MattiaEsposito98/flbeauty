import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import Alert from '../components/Alert'
import AuthCard from '../components/AuthCard'
import PasswordField from '../components/PasswordField'

export default function ResetPassword() {
  const { resetPassword } = useAuth()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setSubmitting(true)

    try {
      await resetPassword({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      navigate('/login?reset=1')
    } catch (err) {
      setError(err.response?.data?.errors?.email?.[0] ?? 'Errore durante il reset della password.')
    } finally {
      setSubmitting(false)
    }
  }

  if (!token || !email) {
    return (
      <AuthCard title="Link non valido">
        <Alert type="error">Il link non è valido. Richiedine uno nuovo dalla pagina di recupero.</Alert>
        <Link to="/password-dimenticata" className="btn btn-primary btn-block btn-lg">
          Richiedi un nuovo link
        </Link>
      </AuthCard>
    )
  }

  return (
    <AuthCard title="Nuova password" subtitle={`Scegli una nuova password per ${email}.`}>
      <form onSubmit={handleSubmit}>
        <PasswordField
          id="reset-password"
          label="Nuova password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete="new-password"
          minLength={8}
          required
          hint="Almeno 8 caratteri."
        />
        <PasswordField
          id="reset-password-confirmation"
          label="Conferma nuova password"
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          autoComplete="new-password"
          minLength={8}
          required
        />
        {error && <Alert type="error">{error}</Alert>}
        <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
          {submitting ? 'Salvataggio...' : 'Reimposta password'}
        </button>
      </form>
    </AuthCard>
  )
}
