export default function Spinner({ label = 'Caricamento...' }) {
  return (
    <div className="spinner" role="status">
      <span className="spinner-ring" aria-hidden="true" />
      <span>{label}</span>
    </div>
  )
}
