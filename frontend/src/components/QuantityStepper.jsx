import { LuMinus, LuPlus } from 'react-icons/lu'

export default function QuantityStepper({ value, onChange, min = 1, max, size }) {
  return (
    <div className={`stepper ${size ? `stepper-${size}` : ''}`}>
      <button
        type="button"
        className="stepper-btn"
        onClick={() => onChange(value - 1)}
        disabled={value <= min}
        aria-label="Diminuisci quantità"
      >
        <LuMinus aria-hidden="true" />
      </button>
      <span className="stepper-value" aria-live="polite">
        {value}
      </span>
      <button
        type="button"
        className="stepper-btn"
        onClick={() => onChange(value + 1)}
        disabled={max != null && value >= max}
        aria-label="Aumenta quantità"
      >
        <LuPlus aria-hidden="true" />
      </button>
    </div>
  )
}
