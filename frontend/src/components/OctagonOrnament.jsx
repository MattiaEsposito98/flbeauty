import { useId } from 'react'

// Doppio ottagono della cornice del logo, riusato come motivo decorativo.
export default function OctagonOrnament({ className = '' }) {
  const gradientId = `octagon-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`

  return (
    <svg viewBox="0 0 100 100" className={className} aria-hidden="true" focusable="false">
      <defs>
        <linearGradient id={gradientId} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#d9a5a0" />
          <stop offset="50%" stopColor="#b76e79" />
          <stop offset="100%" stopColor="#8d6c65" />
        </linearGradient>
      </defs>
      <g fill="none" stroke={`url(#${gradientId})`} strokeWidth="1.2">
        <polygon
          points="30.1,2 69.9,2 98,30.1 98,69.9 69.9,98 30.1,98 2,69.9 2,30.1"
          vectorEffect="non-scaling-stroke"
        />
        <polygon
          points="31.8,6 68.2,6 94,31.8 94,68.2 68.2,94 31.8,94 6,68.2 6,31.8"
          transform="rotate(22.5 50 50)"
          vectorEffect="non-scaling-stroke"
        />
      </g>
    </svg>
  )
}
