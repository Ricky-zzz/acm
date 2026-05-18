import { defineStore } from 'pinia'
import axios from 'axios'
import type { EncryptedEnvelope, ResultImportLog, ResultImportPayload, ResultImportResponse, TallyRow } from '../types'
import { useToastStore } from './toast'

export const useResultStore = defineStore('result', {
  state: () => ({
    tally: [] as TallyRow[],
    importLogs: [] as ResultImportLog[],
    loading: false
  }),

  actions: {
    async importResults(payload: ResultImportPayload | EncryptedEnvelope, method: 'manual' | '3g' = 'manual'): Promise<ResultImportResponse | null> {
      try {
        const toast = useToastStore()
        const response = await axios.post<ResultImportResponse>(`/results/import?method=${method}`, payload)
        toast.success('Results imported')
        return response.data
      } catch (error) {
        console.error('Error importing results:', error)
        return null
      }
    },

    async fetchImportLogs(method?: 'manual' | '3g') {
      try {
        const params = method ? { method } : undefined
        const response = await axios.get<ResultImportLog[]>('/results/import-logs', { params })
        this.importLogs = response.data
      } catch (error) {
        console.error('Error fetching import logs:', error)
      }
    },

    async updateImportLogStatus(id: number, status: 'accepted' | 'rejected'): Promise<boolean> {
      try {
        await axios.post(`/results/import-logs/${id}/status`, { status })
        return true
      } catch (error) {
        console.error('Error updating import log status:', error)
        return false
      }
    },

    async fetchTally(cityId: number) {
      this.loading = true
      try {
        const response = await axios.get<TallyRow[]>(`/results/${cityId}/tally`)
        this.tally = response.data
      } catch (error) {
        console.error('Error fetching tally:', error)
      } finally {
        this.loading = false
      }
    }
  }
})
