import { defineStore } from 'pinia'
import axios from 'axios'
import type { Position } from '../types'
import { useToastStore } from './toast'

export const usePositionStore = defineStore('position', {
  state: () => ({
    positions: [] as Position[],
    loading: false
  }),

  actions: {
    async fetchPositions() {
      this.loading = true
      try {
        const response = await axios.get<Position[]>('/positions')
        this.positions = response.data
      } catch (error) {
        console.error('Error fetching positions:', error)
      } finally {
        this.loading = false
      }
    },

    async addPosition(payload: Position): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.post<Position>('/positions', payload)
        await this.fetchPositions()
        toast.success('Position created')
        return true
      } catch (error) {
        console.error('Error adding position:', error)
        return false
      }
    },

    async updatePosition(id: number, payload: Position): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.put<Position>(`/positions/${id}`, payload)
        await this.fetchPositions()
        toast.success('Position updated')
        return true
      } catch (error) {
        console.error('Error updating position:', error)
        return false
      }
    },

    async deletePosition(id: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.delete(`/positions/${id}`)
        await this.fetchPositions()
        toast.success('Position deleted')
        return true
      } catch (error) {
        console.error('Error deleting position:', error)
        return false
      }
    }
  }
})
