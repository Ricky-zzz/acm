import { defineStore } from 'pinia'
import axios from 'axios'

export interface City {
    id?: number; 
    name: string;
    councilor_limit: number;
    created_at?: string;
}

export interface ApiResponse<T> {
    status: string;
    data: T;
    message?: string;
}

export const useCityStore = defineStore('city', {
  state: () => ({
    cities: [] as City[]
  }),

  actions: {
    async fetchCities() {
      try {
        const response = await axios.get<ApiResponse<City[]>>('http://localhost/acm/counterra/api/cities')
        if (response.data.status === 'success') {
          this.cities = response.data.data
        }
      } catch (error) {
        console.error('Error fetching cities:', error)
      }
    },

    async addCity(name: string, limit: number): Promise<boolean> {
      try {
        const response = await axios.post<ApiResponse<City>>('http://localhost/acm/counterra/api/cities', {
          name,
          councilor_limit: limit
        })
        if (response.data.status === 'success' && response.data.data) {
          this.cities.push(response.data.data)
          return true
        }
        return false
      } catch (error) {
        console.error('Error adding city:', error)
        return false
      }
    },

    async updateCity(id: number, name: string, limit: number): Promise<boolean> {
      try {
        const response = await axios.put<ApiResponse<City>>(`http://localhost/acm/counterra/api/cities/${id}`, {
          name,
          councilor_limit: limit
        })
        if (response.data.status === 'success') {
          await this.fetchCities()
          return true
        }
        return false
      } catch (error) {
        console.error('Error updating city:', error)
        return false
      }
    },

    async deleteCity(id: number): Promise<boolean> {
      try {
        const response = await axios.delete<ApiResponse<void>>(`http://localhost/acm/counterra/api/cities/${id}`)
        if (response.data.status === 'success') {
          await this.fetchCities()
          return true
        }
        return false
      } catch (error) {
        console.error('Error deleting city:', error)
        return false
      }
    }
  }
})