import { defineStore } from 'pinia'

const STORAGE_KEY = 'voterra_admin_auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: typeof window !== 'undefined' && localStorage.getItem(STORAGE_KEY) === '1',
    lastError: ''
  }),

  actions: {
    login(username: string, password: string): boolean {
      this.lastError = ''

      if (username === 'admin' && password === 'admin123') {
        this.isAuthenticated = true
        if (typeof window !== 'undefined') {
          localStorage.setItem(STORAGE_KEY, '1')
        }
        return true
      }

      this.lastError = 'Invalid credentials'
      this.isAuthenticated = false
      if (typeof window !== 'undefined') {
        localStorage.removeItem(STORAGE_KEY)
      }
      return false
    },

    logout() {
      this.isAuthenticated = false
      this.lastError = ''
      if (typeof window !== 'undefined') {
        localStorage.removeItem(STORAGE_KEY)
      }
    }
  }
})
