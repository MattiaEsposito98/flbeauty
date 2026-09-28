// Primo messaggio d'errore di una risposta API: quello del campo richiesto,
// altrimenti l'errore generale del form (`form`: anti-bot, troppi tentativi),
// altrimenti il testo di ripiego.
export function apiError(err, field, fallback) {
  const errors = err.response?.data?.errors
  return errors?.[field]?.[0] ?? errors?.form?.[0] ?? fallback
}
