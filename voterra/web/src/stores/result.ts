import { defineStore } from 'pinia'
import axios from 'axios'
import type { EncryptedEnvelope, LocalTallyRow, ResultStats } from '../types'
import { useToastStore } from './toast'

type ExportScope = 'untransmitted' | 'all'

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

    async exportEncrypted(scope: ExportScope = 'untransmitted', mark = false): Promise<EncryptedEnvelope | null> {
      try {
        const params = new URLSearchParams({
          scope,
          mark: mark ? '1' : '0'
        })
        params.set('encrypted', '1')
        const response = await axios.get<EncryptedEnvelope>(`/results/export-json?${params.toString()}`)
        return response.data
      } catch (error) {
        console.error('Error exporting encrypted results:', error)
        return null
      }
    },

    async markTransmitted(): Promise<void> {
      try {
        await this.exportEncrypted('untransmitted', true)
      } catch (error) {
        console.error('Error marking transmitted results:', error)
      }
    },

    async transmitToParent(parentUrl: string, scope: ExportScope = 'untransmitted'): Promise<boolean> {
      try {
        const toast = useToastStore()
        const payload = await this.exportEncrypted(scope, false)
        if (!payload) {
          return false
        }
        await axios.post(parentUrl, payload)
        await this.markTransmitted()
        toast.success('Results transmitted')
        return true
      } catch (error) {
        console.error('Error transmitting results:', error)
        return false
      }
    }
  }
})
