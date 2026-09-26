import { useEffect, useRef, useState } from 'react'
import client from '../api/client'

/**
 * Campo di ricerca comune con suggerimenti. Notifica il genitore con
 * l'id del comune scelto tramite onSelect.
 */
export default function ComuneAutocomplete({ value, onSelect, required }) {
  const [query, setQuery] = useState(value?.name ?? '')
  const [options, setOptions] = useState([])
  const [open, setOpen] = useState(false)
  const debounceRef = useRef(null)

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current)

    if (query.trim().length < 2) {
      setOptions([])
      return
    }

    debounceRef.current = setTimeout(async () => {
      const { data } = await client.get('/comuni', { params: { search: query.trim() } })
      setOptions(data)
    }, 250)

    return () => clearTimeout(debounceRef.current)
  }, [query])

  function handleChoose(comune) {
    setQuery(comune.name)
    setOpen(false)
    onSelect(comune)
  }

  return (
    <div className="field autocomplete">
      <label>Comune{required && ' *'}</label>
      <input
        type="text"
        value={query}
        placeholder="Inizia a scrivere il comune..."
        onChange={(e) => {
          setQuery(e.target.value)
          setOpen(true)
          onSelect(null)
        }}
        onFocus={() => setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        required={required}
      />
      {open && options.length > 0 && (
        <ul className="autocomplete-list">
          {options.map((comune) => (
            <li key={comune.id} onMouseDown={() => handleChoose(comune)}>
              {comune.name}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
