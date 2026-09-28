import { createContext, useContext, useEffect, useState } from 'react'
import client from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('token')

    if (!token) {
      setLoading(false)
      return
    }

    client
      .get('/user')
      .then(({ data }) => setUser(data))
      .catch(() => localStorage.removeItem('token'))
      .finally(() => setLoading(false))
  }, [])

  // `identifier` può essere l'email o lo username.
  async function login(identifier, password, botFields = {}) {
    const { data } = await client.post('/login', { login: identifier, password, ...botFields })
    localStorage.setItem('token', data.token)
    setUser(data.user)
  }

  async function register(payload) {
    // Non fa auto-login: l'account va verificato via email prima di poter accedere.
    const { data } = await client.post('/register', payload)
    return data
  }

  async function resendVerificationEmail(identifier) {
    const { data } = await client.post('/email/verification-notification', { login: identifier })
    return data
  }

  async function forgotPassword(email, botFields = {}) {
    const { data } = await client.post('/forgot-password', { email, ...botFields })
    return data
  }

  async function resetPassword(payload) {
    const { data } = await client.post('/reset-password', payload)
    return data
  }

  async function logout() {
    await client.post('/logout').catch(() => {})
    localStorage.removeItem('token')
    setUser(null)
  }

  async function updateMarketingConsent(consent) {
    const { data } = await client.patch('/user/marketing-consent', { marketing_consent: consent })
    setUser(data)
  }

  async function changePassword(payload) {
    const { data } = await client.put('/user/password', payload)
    return data
  }

  // Dopo l'eliminazione si ricarica la pagina di login da zero: così carrello,
  // preferiti e utente ripartono puliti, e la pagina protetta dell'account non
  // fa in tempo a rimandare al login normale (senza il messaggio di conferma).
  async function deleteAccount(password) {
    await client.delete('/user', { data: { password } })
    localStorage.removeItem('token')
    window.location.replace('/login?deleted=1')
  }

  async function refreshUser() {
    const { data } = await client.get('/user')
    setUser(data)
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        login,
        register,
        logout,
        refreshUser,
        updateMarketingConsent,
        changePassword,
        deleteAccount,
        resendVerificationEmail,
        forgotPassword,
        resetPassword,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth deve essere usato dentro <AuthProvider>')
  }

  return context
}
