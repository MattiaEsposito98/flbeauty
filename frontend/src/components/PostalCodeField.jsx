/**
 * Campo CAP che si adatta al comune scelto: un solo CAP valido -> valore
 * bloccato, più CAP validi -> tendina, nessun comune scelto -> disabilitato.
 */
export default function PostalCodeField({ comune, value, onChange }) {
  const options = comune?.postal_codes ?? []

  if (!comune) {
    return (
      <div className="field">
        <label>CAP *</label>
        <input value="" disabled placeholder="Seleziona prima un comune" />
      </div>
    )
  }

  if (options.length <= 1) {
    return (
      <div className="field">
        <label>CAP *</label>
        <input value={options[0] ?? value} disabled readOnly />
      </div>
    )
  }

  return (
    <div className="field">
      <label>CAP *</label>
      <select value={value} onChange={(e) => onChange(e.target.value)} required>
        <option value="">Seleziona il CAP</option>
        {options.map((cap) => (
          <option key={cap} value={cap}>
            {cap}
          </option>
        ))}
      </select>
    </div>
  )
}
