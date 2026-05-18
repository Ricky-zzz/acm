import { defineStore } from 'pinia'
import axios from 'axios'
import type { EncryptedEnvelope, SetupPayload, SetupStatus } from '../types'
import { useToastStore } from './toast'

export const useSetupStore = defineStore('setup', {
  state: () => ({
    status: null as SetupStatus | null,
    lastError: '',
    lastMessage: '',
    loading: false
  }),

  actions: {
    async fetchStatus() {
      this.loading = true
      try {
        const response = await axios.get<SetupStatus>('/setup/status')
        this.status = response.data
      } catch (error) {
        console.error('Error fetching setup status:', error)
      } finally {
        this.loading = false
      }
    },

    async importJson(payload: SetupPayload | EncryptedEnvelope): Promise<boolean> {
      const toast = useToastStore()
      this.lastError = ''
      this.lastMessage = ''

      try {
        const response = await axios.post<{ status?: string; message?: string }>('/setup/import', payload)
        await this.fetchStatus()
        this.lastMessage = response.data.message || response.data.status || 'Import completed'
        toast.success(this.lastMessage)
        return true
      } catch (error) {
        this.lastError = axios.isAxiosError(error)
          ? error.response?.data?.message || 'Import failed'
          : 'Import failed'
        console.error('Error importing setup JSON:', error)
        return false
      }
    },

    async wipe(): Promise<boolean> {
      const toast = useToastStore()
      this.lastError = ''
      this.lastMessage = ''

      try {
        const response = await axios.post<{ status?: string; message?: string }>('/setup/wipe')
        await this.fetchStatus()
        this.lastMessage = response.data.message || response.data.status || 'Machine wiped'
        toast.success(this.lastMessage)
        return true
      } catch (error) {
        this.lastError = axios.isAxiosError(error)
          ? error.response?.data?.message || 'Wipe failed'
          : 'Wipe failed'
        console.error('Error wiping machine:', error)
        return false
      }
    },

    async resetExportLock(): Promise<boolean> {
      const toast = useToastStore()
      this.lastError = ''
      this.lastMessage = ''

      try {
        const response = await axios.post<{ status?: string; message?: string }>('/setup/reset-export-lock')
        await this.fetchStatus()
        this.lastMessage = response.data.message || response.data.status || 'Export lock reset'
        toast.success(this.lastMessage)
        return true
      } catch (error) {
        this.lastError = axios.isAxiosError(error)
          ? error.response?.data?.message || 'Reset failed'
          : 'Reset failed'
        console.error('Error resetting export lock:', error)
        return false
      }
    },
  }
})
