import { defineStore } from 'pinia'
import axios from 'axios'
import type { SetupPayload, SetupStatus } from '../types'
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

    async importJson(payload: SetupPayload): Promise<boolean> {
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

    async importCsv(file: File | Blob): Promise<boolean> {
      try {
        const toast = useToastStore()
        const formData = new FormData()
        const filename = file instanceof File ? file.name : 'setup.csv'
        formData.append('file', file, filename)
        await axios.post('/setup/import-csv', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        await this.fetchStatus()
        toast.success('Machine initialized')
        return true
      } catch (error) {
        console.error('Error importing setup CSV:', error)
        return false
      }
    }
  }
})
