import { useRef, useState } from 'react'

// Anti-bot lato form (vedi backend App\Http\Middleware\BlockBots):
// - `trap` è un campo "website" fuori dallo schermo: le persone non lo vedono,
//   i bot che compilano tutto lo riempiono e la richiesta viene respinta
// - `botFields()` va aggiunto al payload: contiene il campo trappola e il tempo
//   trascorso da quando il form è stato mostrato
export function useBotTrap() {
  const [website, setWebsite] = useState('')
  const shownAt = useRef(Date.now())

  const trap = (
    <div className="bot-trap" aria-hidden="true">
      <label htmlFor="bot-trap-website">Lascia vuoto questo campo</label>
      <input
        id="bot-trap-website"
        name="website"
        type="text"
        tabIndex={-1}
        autoComplete="off"
        value={website}
        onChange={(e) => setWebsite(e.target.value)}
      />
    </div>
  )

  function botFields() {
    return { website, form_time: Date.now() - shownAt.current }
  }

  return { trap, botFields }
}
