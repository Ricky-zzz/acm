import { defineStore } from 'pinia'
import axios from 'axios'
import type { LocalTallyRow, ResultExportPayload, ResultStats } from '../types'
import { useToastStore } from './toast'

export const useResultStore = defineStore('result', {
  state: () => ({
    tally: [] as LocalTallyRow[],
    stats: null as ResultStats | null,
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

    async exportJson(): Promise<ResultExportPayload | null> {
      try {
        const response = await axios.get<ResultExportPayload>('/results/export-json')
        return response.data
      } catch (error) {
        console.error('Error exporting results JSON:', error)
        return null
      }
    },

    async transmitToParent(parentUrl: string): Promise<boolean> {
      try {
        const toast = useToastStore()
        const payload = await this.exportJson()
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
