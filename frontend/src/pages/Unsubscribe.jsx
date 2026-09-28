import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import client from '../api/client'
import Alert from '../components/Alert'
import AuthCard from '../components/AuthCard'
import { apiError } from '../utils/apiError'

// Aperta dal link "Disiscriviti" in fondo alle email promozionali. Chiede una
// conferma prima di disiscrivere: i filtri antispam aprono i link delle email in
// automatico, e senza conferma disiscriverebbero il cliente da soli.
export default function Unsubscribe() {
  const [searchParams] = useSearchParams()
  const userId = searchParams.get('u')
  const signature = searchParams.get('signature')
  const [message, setMessage] = useState(null)
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleConfirm() {
    setError(null)
    setSubmitting(true)

    try {
      const { data } = await client.post(`/unsubscribe/${encodeURIComponent(userId)}`, null, {
        params: { signature },
      })
      setMessage(data.message)
    } catch (err) {
      setError(
        err.response?.status === 403
          ? 'Il link non è valido. Puoi disattivare le email promozionali dal tuo account.'
          : apiError(err, 'form', 'Qualcosa non ha funzionato. Riprova tra poco.')
      )
    } finally {
      setSubmitting(false)
    }
  }

  const footer = (
    <>
      Puoi gestire le tue preferenze anche dal <Link to="/account">tuo account</Link>.
    </>
  )

  if (!userId || !signature) {
    return (
      <AuthCard title="Link non valido" footer={footer}>
        <Alert type="error">Il link di disiscrizione è incompleto. Aprilo di nuovo dall'email ricevuta.</Alert>
      </AuthCard>
    )
  }

  return (
    <AuthCard
      title="Email promozionali"
      subtitle="Vuoi smettere di ricevere offerte e novità da F&L Beauty?"
      footer={footer}
    >
      {message ? (
        <Alert type="success">{message}</Alert>
      ) : (
        <>
          <p className="hint">
            Continuerai comunque a ricevere le email sui tuoi ordini e le comunicazioni importanti sul
            tuo account.
          </p>
          {error && <Alert type="error">{error}</Alert>}
          <button
            type="button"
            className="btn btn-primary btn-block btn-lg"
            onClick={handleConfirm}
            disabled={submitting}
          >
            {submitting ? 'Un attimo...' : 'Sì, disiscrivimi'}
          </button>
        </>
      )}
    </AuthCard>
  )
}
