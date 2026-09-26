import { useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

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
    setResendStatus('invio...')
    const { message } = await resendVerificationEmail(email)
    setResendStatus(message)
  }

  const showResend = error?.includes(UNVERIFIED_HINT)

  return (
    <div className="page page-login">
      <h1>Accedi</h1>

      {justVerified && <p className="hint">Email verificata! Ora puoi accedere.</p>}

      <form onSubmit={handleSubmit}>
        <div className="field">
          <label>Email</label>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        </div>
        <div className="field">
          <label>Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </div>
        {error && <p className="error">{error}</p>}
        {showResend && (
          <p className="hint">
            Non hai ricevuto l'email? <a onClick={handleResend}>Invia di nuovo</a>
          </p>
        )}
        {resendStatus && <p className="hint">{resendStatus}</p>}
        <button type="submit" disabled={submitting}>
          {submitting ? 'Accesso...' : 'Accedi'}
        </button>
      </form>
    </div>
  )
}
