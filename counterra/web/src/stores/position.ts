import { defineStore } from 'pinia'
import axios from 'axios'
import type { Position, ApiResponse } from '../types'

export const usePositionStore = defineStore('position', {
  state: () => ({
    positions: [] as Position[],
    loading: false
  }),

  actions: {
    async fetchPositions() {
      this.loading = true
      try {
        const response = await axios.get<ApiResponse<Position[]>>('http://localhost/acm/counterra/api/positions')
        if (response.data.status === 'success') {
          this.positions = response.data.data
        }
      } catch (error) {
        console.error('Error fetching positions:', error)
      } finally {
        this.loading = false
      }
    },

    async addPosition(payload: Position): Promise<boolean> {
      try {
        const response = await axios.post<ApiResponse<Position>>('http://localhost/acm/counterra/api/positions', payload)
        if (response.data.status === 'success') {
          await this.fetchPositions()
          return true
        }
        return false
      } catch (error) {
        console.error('Error adding position:', error)
        return false
      }
    },

    async updatePosition(id: number, payload: Position): Promise<boolean> {
      try {
        const response = await axios.put<ApiResponse<Position>>(`http://localhost/acm/counterra/api/positions/${id}`, payload)
        if (response.data.status === 'success') {
          await this.fetchPositions()
          return true
        }
        return false
      } catch (error) {
        console.error('Error updating position:', error)
        return false
      }
    },

    async deletePosition(id: number): Promise<boolean> {
      try {
        const response = await axios.delete<ApiResponse<void>>(`http://localhost/acm/counterra/api/positions/${id}`)
        if (response.data.status === 'success') {
          await this.fetchPositions()
          return true
        }
        return false
      } catch (error) {
        console.error('Error deleting position:', error)
        return false
      }
    }
  }
})
