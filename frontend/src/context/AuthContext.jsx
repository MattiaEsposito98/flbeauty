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

  async function login(email, password) {
    const { data } = await client.post('/login', { email, password })
    localStorage.setItem('token', data.token)
    setUser(data.user)
  }

  async function register(payload) {
    // Non fa auto-login: l'account va verificato via email prima di poter accedere.
    const { data } = await client.post('/register', payload)
    return data
  }

  async function resendVerificationEmail(email) {
    const { data } = await client.post('/email/verification-notification', { email })
    return data
  }

  async function forgotPassword(email) {
    const { data } = await client.post('/forgot-password', { email })
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
