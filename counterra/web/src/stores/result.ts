import { defineStore } from 'pinia'
import axios from 'axios'
import type { ResultImportPayload, ResultImportResponse, TallyRow } from '../types'
import { useToastStore } from './toast'

export const useResultStore = defineStore('result', {
  state: () => ({
    tally: [] as TallyRow[],
    loading: false
  }),

  actions: {
    async importResults(payload: ResultImportPayload): Promise<ResultImportResponse | null> {
      try {
        const toast = useToastStore()
        const response = await axios.post<ResultImportResponse>('/results/import', payload)
        toast.success('Results imported')
        return response.data
      } catch (error) {
        console.error('Error importing results:', error)
        return null
      }
    },

    async importResultsCsv(file: File): Promise<ResultImportResponse | null> {
      try {
        const toast = useToastStore()
        const formData = new FormData()
        formData.append('file', file)
        const response = await axios.post<ResultImportResponse>('/results/import-csv', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        toast.success('Results imported')
        return response.data
      } catch (error) {
        console.error('Error importing CSV results:', error)
        return null
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
