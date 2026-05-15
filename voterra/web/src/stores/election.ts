import { defineStore } from 'pinia'
import axios from 'axios'
import type { BallotValidationResponse, SetupCandidate, SetupPosition, SetupStatus, VotePayload } from '../types'
import { useToastStore } from './toast'

export const useElectionStore = defineStore('election', {
  state: () => ({
    settings: null as SetupStatus | null,
    positions: [] as SetupPosition[],
    candidates: [] as SetupCandidate[],
    loading: false,
    ballotStatus: null as BallotValidationResponse | null
  }),

  actions: {
    async fetchSetup() {
      this.loading = true
      try {
        const [settingsRes, posRes, candRes] = await Promise.all([
          axios.get<SetupStatus>('/setup/status'),
          axios.get<SetupPosition[]>('/positions'),
          axios.get<SetupCandidate[]>('/candidates')
        ])
        this.settings = settingsRes.data
        this.positions = posRes.data
        this.candidates = candRes.data
      } catch (error) {
        console.error('Error fetching setup data:', error)
      } finally {
        this.loading = false
      }
    },

    async validateBallot(ballotNumber: string): Promise<BallotValidationResponse | null> {
      try {
        const response = await axios.post<BallotValidationResponse>('/ballots/validate', {
          ballot_number: ballotNumber
        })
        this.ballotStatus = response.data
        return response.data
      } catch (error) {
        console.error('Error validating ballot:', error)
        return null
      }
    },

    async castVote(payload: VotePayload): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.post('/votes', payload)
        toast.success('Vote recorded')
        return true
      } catch (error) {
        console.error('Error casting vote:', error)
        return false
      }
    }
  }
})
