import { defineStore } from 'pinia'
import axios from 'axios'
import type { Party } from '../types'
import { useToastStore } from './toast'

export const usePartyStore = defineStore('party', {
  state: () => ({
    parties: [] as Party[]
  }),

  actions: {
    async fetchParties() {
      try {
        const response = await axios.get<Party[]>('/parties')
        this.parties = response.data
      } catch (error) {
        console.error('Error fetching parties:', error)
      }
    },

    async addParty(name: string, alias: string): Promise<boolean> {
      try {
        const toast = useToastStore()
        const response = await axios.post<Party>('/parties', {
          name,
          alias
        })

        this.parties.push(response.data)
        toast.success('Party created')
        return true
      } catch (error) {
        console.error('Error adding party:', error)
        return false
      }
    },

    async updateParty(id: number, name: string, alias: string): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.put<Party>(`/parties/${id}`, {
          name,
          alias
        })

        await this.fetchParties()
        toast.success('Party updated')
        return true
      } catch (error) {
        console.error('Error updating party:', error)
        return false
      }
    },

    async deleteParty(id: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.delete(`/parties/${id}`)
        await this.fetchParties()
        toast.success('Party deleted')
        return true
      } catch (error) {
        console.error('Error deleting party:', error)
        return false
      }
    }
  }
})
