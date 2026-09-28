import { useState } from 'react'
import { Link } from 'react-router-dom'
import { LuArrowLeft, LuMail } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import Alert from '../components/Alert'
import AuthCard from '../components/AuthCard'
import { useBotTrap } from '../components/BotTrap'
import { apiError } from '../utils/apiError'

export default function ForgotPassword() {
  const { forgotPassword } = useAuth()
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState(null)
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const { trap, botFields } = useBotTrap()

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setSubmitting(true)

    try {
      const { message } = await forgotPassword(email, botFields())
      setMessage(message)
    } catch (err) {
      setError(apiError(err, 'email', 'Errore durante l\'invio. Riprova più tardi.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthCard
      title="Password dimenticata?"
      subtitle="Nessun problema: inserisci la tua email e ti invieremo un link per reimpostarla."
      footer={
        <Link to="/login" className="back-link">
          <LuArrowLeft aria-hidden="true" /> Torna al login
        </Link>
      }
    >
      {message ? (
        <Alert type="success">{message}</Alert>
      ) : (
        <form onSubmit={handleSubmit}>
          {trap}
          <div className="field">
            <label htmlFor="forgot-email">Email</label>
            <div className="input-icon">
              <LuMail aria-hidden="true" />
              <input
                id="forgot-email"
                type="email"
                autoComplete="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
          </div>
          {error && <Alert type="error">{error}</Alert>}
          <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
            {submitting ? 'Invio...' : 'Invia link di recupero'}
          </button>
        </form>
      )}
    </AuthCard>
  )
}
