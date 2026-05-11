import { defineStore } from 'pinia'
import axios from 'axios'
import type { AuthResponse, User } from '../types'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null') as User | null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
  }),

  actions: {
    async login(username: string, password: string): Promise<boolean> {
      try {
        const response = await axios.post<AuthResponse>('http://localhost/acm/counterra/api/login', {
          username,
          password
        });

        if (response.data.status === 'success') {
          this.user = response.data.user || null;
          this.token = response.data.token || null;
          this.isAuthenticated = true;

          // Save to persistent storage
          if (this.token) localStorage.setItem('token', this.token);
          if (this.user) localStorage.setItem('user', JSON.stringify(this.user));
          return true;
        }
        return false;
      } catch (error) {
        console.error("Auth Error:", error);
        return false;
      }
    },

    logout() {
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  }
})