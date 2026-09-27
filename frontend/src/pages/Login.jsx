import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { LuMail } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import Alert from '../components/Alert'
import AuthCard from '../components/AuthCard'
import PasswordField from '../components/PasswordField'

const UNVERIFIED_HINT = 'Devi verificare la tua email prima di accedere'

export default function Login() {
  const { login, resendVerificationEmail } = useAuth()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [resendStatus, setResendStatus] = useState(null)

  const justVerified = searchParams.get('verified') === '1'
  const justReset = searchParams.get('reset') === '1'

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setResendStatus(null)
    setSubmitting(true)

    try {
      await login(email, password)
      navigate(searchParams.get('redirect') || '/account')
    } catch (err) {
      setError(err.response?.data?.errors?.email?.[0] ?? "Errore durante l'accesso.")
    } finally {
      setSubmitting(false)
    }
  }

  async function handleResend() {
    setResendStatus('Invio in corso...')
    const { message } = await resendVerificationEmail(email)
    setResendStatus(message)
  }

  const showResend = error?.includes(UNVERIFIED_HINT)

  return (
    <AuthCard
      title="Accedi"
      subtitle="Che bello rivederti! Entra nel tuo account F&L Beauty."
      footer={
        <>
          Non hai ancora un account? <Link to="/register">Registrati</Link>
        </>
      }
    >
      {justVerified && <Alert type="success">Email verificata! Ora puoi accedere.</Alert>}
      {justReset && <Alert type="success">Password reimpostata! Ora puoi accedere.</Alert>}

      <form onSubmit={handleSubmit}>
        <div className="field">
          <label htmlFor="login-email">Email</label>
          <div className="input-icon">
            <LuMail aria-hidden="true" />
            <input
              id="login-email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>
        </div>
        <PasswordField
          id="login-password"
          label="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete="current-password"
          required
        />

        <Link to="/password-dimenticata" className="auth-inline-link">
          Password dimenticata?
        </Link>

        {error && (
          <Alert type="error">
            <p>{error}</p>
            {showResend && (
              <p>
                Non hai ricevuto l'email?{' '}
                <button type="button" className="link-button" onClick={handleResend}>
                  Invia di nuovo
                </button>
              </p>
            )}
          </Alert>
        )}
        {resendStatus && <Alert type="info">{resendStatus}</Alert>}

        <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
          {submitting ? 'Accesso...' : 'Accedi'}
        </button>
      </form>
    </AuthCard>
  )
}
