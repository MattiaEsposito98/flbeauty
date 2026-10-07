import axios from 'axios'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
})

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

// Se il server rifiuta l'accesso (401) a una richiesta fatta con un token, la
// sessione non vale più: scaduta, chiusa da un cambio password o account bloccato
// dall'admin. Lo comunichiamo ad AuthContext, che fa uscire l'utente subito invece
// di lasciarlo "loggato" in una pagina che non funziona più.
client.interceptors.response.use(
  (response) => response,
  (error) => {
    const sentToken = Boolean(error.config?.headers?.Authorization)

    if (error.response?.status === 401 && sentToken && localStorage.getItem('token')) {
      window.dispatchEvent(new Event('auth:expired'))
    }

    return Promise.reject(error)
  },
)

export default client
