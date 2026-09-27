import { createContext, useContext, useEffect, useState } from 'react'
import client from '../api/client'
import { useAuth } from './AuthContext'

const WishlistContext = createContext(null)

export function WishlistProvider({ children }) {
  const { user } = useAuth()
  const [products, setProducts] = useState([])
  const [loaded, setLoaded] = useState(false)

  useEffect(() => {
    if (!user) {
      setProducts([])
      setLoaded(false)
      return
    }

    client.get('/wishlist').then(({ data }) => {
      setProducts(data.data)
      setLoaded(true)
    })
  }, [user])

  function isWishlisted(productId) {
    return products.some((product) => product.id === productId)
  }

  async function add(product) {
    setProducts((prev) => (prev.some((p) => p.id === product.id) ? prev : [...prev, product]))

    try {
      await client.post(`/wishlist/${product.id}`)
    } catch {
      setProducts((prev) => prev.filter((p) => p.id !== product.id))
    }
  }

  async function remove(productId) {
    const previous = products
    setProducts((prev) => prev.filter((p) => p.id !== productId))

    try {
      await client.delete(`/wishlist/${productId}`)
    } catch {
      setProducts(previous)
    }
  }

  async function toggle(product) {
    if (isWishlisted(product.id)) {
      await remove(product.id)
    } else {
      await add(product)
    }
  }

  return (
    <WishlistContext.Provider value={{ products, loaded, isWishlisted, toggle }}>
      {children}
    </WishlistContext.Provider>
  )
}

export function useWishlist() {
  const context = useContext(WishlistContext)

  if (!context) {
    throw new Error('useWishlist deve essere usato dentro <WishlistProvider>')
  }

  return context
}
