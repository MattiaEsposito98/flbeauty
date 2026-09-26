import { useEffect, useState } from 'react'
import ComuneAutocomplete from './ComuneAutocomplete'
import PostalCodeField from './PostalCodeField'

const emptyForm = {
  label: '',
  recipient_name: '',
  phone: '',
  address_line: '',
  postal_code: '',
  is_default: false,
}

export default function AddressForm({ initial, onSubmit, onCancel }) {
  const [form, setForm] = useState({ ...emptyForm, ...initial })
  const [comune, setComune] = useState(initial?.comune ?? null)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    const options = comune?.postal_codes ?? []

    if (options.length === 1) {
      setForm((f) => ({ ...f, postal_code: options[0] }))
    } else if (comune?.id !== initial?.comune?.id) {
      setForm((f) => ({ ...f, postal_code: '' }))
    }
  }, [comune])

  function handleChange(e) {
    const { name, type, checked, value } = e.target
    setForm({ ...form, [name]: type === 'checkbox' ? checked : value })
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setSubmitting(true)

    try {
      await onSubmit({ ...form, comune_id: comune?.id })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="address-form" onSubmit={handleSubmit}>
      <div className="field">
        <label>Nome indirizzo (es. Casa, Ufficio)</label>
        <input name="label" value={form.label ?? ''} onChange={handleChange} />
      </div>
      <div className="field">
        <label>Destinatario *</label>
        <input name="recipient_name" value={form.recipient_name} onChange={handleChange} required />
      </div>
      <div className="field">
        <label>Telefono</label>
        <input name="phone" value={form.phone ?? ''} onChange={handleChange} />
      </div>
      <div className="field">
        <label>Via e civico *</label>
        <input name="address_line" value={form.address_line} onChange={handleChange} required />
      </div>

      <ComuneAutocomplete value={comune} onSelect={setComune} required />

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

      <label className="checkbox">
        <input type="checkbox" name="is_default" checked={form.is_default} onChange={handleChange} />
        Imposta come indirizzo principale
      </label>

      <div className="actions">
        <button type="submit" disabled={submitting}>
          {submitting ? 'Salvataggio...' : 'Salva indirizzo'}
        </button>
        {onCancel && (
          <button type="button" onClick={onCancel} className="secondary">
            Annulla
          </button>
        )}
      </div>
    </form>
  )
}
