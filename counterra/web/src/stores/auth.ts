import { defineStore } from 'pinia'
import axios from 'axios'
import type { AuthResponse, User } from '../types'
import { useToastStore } from './toast'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null') as User | null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
  }),

  actions: {
    async login(username: string, password: string): Promise<boolean> {
      try {
        const toast = useToastStore()
        const response = await axios.post<AuthResponse>('/login', {
          username,
          password
        });

        this.user = response.data.user;
        this.token = response.data.token;
        this.isAuthenticated = true;

        localStorage.setItem('token', this.token);
        localStorage.setItem('user', JSON.stringify(this.user));
        toast.success('Logged in')
        return true;
      } catch (error) {
        console.error("Auth Error:", error);
        return false;
      }
    },

    logout() {
      const toast = useToastStore()
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      toast.info('Logged out')
    }
  }
})