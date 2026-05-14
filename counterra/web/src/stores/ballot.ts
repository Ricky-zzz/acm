import { defineStore } from 'pinia'
import axios from 'axios'
import type { Ballot } from '../types'
import { useToastStore } from './toast'

export type BallotStatusFilter = 'all' | 'unused' | 'used'

export const useBallotStore = defineStore('ballot', {
  state: () => ({
    ballots: [] as Ballot[],
    loading: false
  }),

  actions: {
    async fetchBallots(cityId?: number) {
      this.loading = true
      try {
        const params: Record<string, string | number> = {}
        if (cityId) {
          params.city_id = cityId
        }

        const response = await axios.get<Ballot[]>('/ballots', { params })
        this.ballots = response.data
      } catch (error) {
        console.error('Error fetching ballots:', error)
      } finally {
        this.loading = false
      }
    },

    async generateBallots(cityId: number, quantity: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.post('/ballots/generate', { city_id: cityId, quantity })
        await this.fetchBallots()
        toast.success('Ballots generated')
        return true
      } catch (error) {
        console.error('Error generating ballots:', error)
        return false
      }
    }
  }
})
