import { defineStore } from 'pinia'
import axios from 'axios'
import type { Candidate } from '../types'
import { useToastStore } from './toast'

export const useCandidateStore = defineStore('candidate', {
  state: () => ({
    candidates: [] as Candidate[],
    loading: false
  }),

  actions: {
    async fetchCandidates() {
      this.loading = true
      try {
        const response = await axios.get<Candidate[]>('/candidates')
        this.candidates = response.data
      } catch (error) {
        console.error('Error fetching candidates:', error)
      } finally {
        this.loading = false
      }
    },

    async addCandidate(payload: Pick<Candidate, 'name' | 'position_id' | 'party_id'>): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.post<Candidate>('/candidates', payload)
        await this.fetchCandidates()
        toast.success('Candidate created')
        return true
      } catch (error) {
        console.error('Error adding candidate:', error)
        return false
      }
    },

    async updateCandidate(id: number, payload: Pick<Candidate, 'name' | 'position_id' | 'party_id'>): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.put<Candidate>(`/candidates/${id}`, payload)
        await this.fetchCandidates()
        toast.success('Candidate updated')
        return true
      } catch (error) {
        console.error('Error updating candidate:', error)
        return false
      }
    },

    async deleteCandidate(id: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.delete(`/candidates/${id}`)
        await this.fetchCandidates()
        toast.success('Candidate deleted')
        return true
      } catch (error) {
        console.error('Error deleting candidate:', error)
        return false
      }
    }
  }
})
