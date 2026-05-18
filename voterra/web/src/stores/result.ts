import { defineStore } from 'pinia'
import axios from 'axios'
import type { EncryptedEnvelope, ExportLogEntry, LocalTallyRow, ResultStats } from '../types'
import { useToastStore } from './toast'

type ExportMethod = 'manual' | '3g'

export const useResultStore = defineStore('result', {
  state: () => ({
    tally: [] as LocalTallyRow[],
    stats: null as ResultStats | null,
    exportLogs: [] as ExportLogEntry[],
    loading: false
  }),

  actions: {
    async fetchTally() {
      this.loading = true
      try {
        const response = await axios.get<LocalTallyRow[]>('/results/tally')
        this.tally = response.data
      } catch (error) {
        console.error('Error fetching tally:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchStats() {
      try {
        const response = await axios.get<ResultStats>('/results/stats')
        this.stats = response.data
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    },

    async fetchExportLogs(method?: ExportMethod) {
      try {
        const params = method ? { method } : undefined
        const response = await axios.get<ExportLogEntry[]>('/results/export-logs', { params })
        this.exportLogs = response.data
      } catch (error) {
        console.error('Error fetching export logs:', error)
      }
    },

    async exportEncrypted(method: ExportMethod = 'manual'): Promise<EncryptedEnvelope | null> {
      try {
        const params = new URLSearchParams({ method })
        params.set('encrypted', '1')
        const response = await axios.get<EncryptedEnvelope>(`/results/export-json?${params.toString()}`)
        return response.data
      } catch (error) {
        console.error('Error exporting encrypted results:', error)
        return null
      }
    },

    async transmitToParent(parentUrl: string): Promise<boolean> {
      try {
        const toast = useToastStore()
        const payload = await this.exportEncrypted('3g')
        if (!payload) {
          return false
        }
        await axios.post(parentUrl, payload)
        toast.success('Results transmitted')
        return true
      } catch (error) {
        console.error('Error transmitting results:', error)
        return false
      }
    }
  }
})
