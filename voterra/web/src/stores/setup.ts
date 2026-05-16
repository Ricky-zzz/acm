import { defineStore } from 'pinia'
import axios from 'axios'
import type { EncryptedEnvelope, SetupPayload, SetupStatus } from '../types'
import { useToastStore } from './toast'

export const useSetupStore = defineStore('setup', {
  state: () => ({
    status: null as SetupStatus | null,
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
      try {
        const toast = useToastStore()
        await axios.post('/setup/import', payload)
        await this.fetchStatus()
        toast.success('Machine initialized')
        return true
      } catch (error) {
        console.error('Error importing setup JSON:', error)
        return false
      }
    },
  }
})
