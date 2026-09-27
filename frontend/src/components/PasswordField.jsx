import { useId, useState } from 'react'
import { LuEye, LuEyeOff, LuLock } from 'react-icons/lu'

export default function PasswordField({
  label,
  value,
  onChange,
  autoComplete = 'current-password',
  required = false,
  minLength,
  error,
  hint,
  id,
}) {
  const generatedId = useId()
  const fieldId = id ?? generatedId
  const [visible, setVisible] = useState(false)

  return (
    <div className="field">
      <label htmlFor={fieldId}>{label}</label>
      <div className="input-icon has-toggle">
        <LuLock aria-hidden="true" />
        <input
          id={fieldId}
          type={visible ? 'text' : 'password'}
          autoComplete={autoComplete}
          value={value}
          onChange={onChange}
          minLength={minLength}
          required={required}
        />
        <button
          type="button"
          className="password-toggle"
          onClick={() => setVisible((v) => !v)}
          aria-label={visible ? 'Nascondi password' : 'Mostra password'}
          aria-pressed={visible}
        >
          {visible ? <LuEyeOff aria-hidden="true" /> : <LuEye aria-hidden="true" />}
        </button>
      </div>
      {error && <p className="error">{error}</p>}
      {hint && <p className="hint">{hint}</p>}
    </div>
  )
}
