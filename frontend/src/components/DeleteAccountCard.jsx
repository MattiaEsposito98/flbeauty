import { useState } from 'react'
import { LuTrash2 } from 'react-icons/lu'
import { useAuth } from '../context/AuthContext'
import { apiError } from '../utils/apiError'
import Alert from './Alert'
import ConfirmButton from './ConfirmButton'
import PasswordField from './PasswordField'

// In fondo alla pagina account. Serve la password e un doppio click sul
// pulsante: l'eliminazione non si può annullare.
export default function DeleteAccountCard() {
  const { deleteAccount } = useAuth()
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleDelete() {
    if (!password) {
      setError('Inserisci la tua password per confermare.')
      return
    }

    setError(null)
    setSubmitting(true)

    try {
      await deleteAccount(password)
    } catch (err) {
      setError(apiError(err, 'password', "Non è stato possibile eliminare l'account. Riprova."))
      setSubmitting(false)
    }
  }

  return (
    <section className="card danger-zone">
      <div className="card-header">
        <h2 className="card-title">
          <LuTrash2 aria-hidden="true" /> Elimina account
        </h2>
      </div>

      <p className="hint">
        Verranno cancellati il tuo account, gli indirizzi, il carrello e i preferiti. L'operazione non
        si può annullare. Gli ordini già effettuati restano registrati come richiesto dalla legge
        (documenti fiscali), ma non saranno più collegati a nessun account.
      </p>

      <div className="danger-zone-actions">
        <PasswordField
          id="delete-account-password"
          label="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete="current-password"
        />
        <ConfirmButton
          className="btn btn-outline btn-outline-danger"
          confirmLabel="Clicca di nuovo per confermare"
          onConfirm={handleDelete}
          disabled={submitting}
        >
          <LuTrash2 aria-hidden="true" /> {submitting ? 'Eliminazione...' : 'Elimina il mio account'}
        </ConfirmButton>
      </div>

      {error && <Alert type="error">{error}</Alert>}
    </section>
  )
}
