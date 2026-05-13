import { defineStore } from 'pinia'
import axios from 'axios'
import type { City } from '../types'
import { useToastStore } from './toast'

export const useCityStore = defineStore('city', {
  state: () => ({
    cities: [] as City[]
  }),

  actions: {
    async fetchCities() {
      try {
        const response = await axios.get<City[]>('/cities')
        this.cities = response.data
      } catch (error) {
        console.error('Error fetching cities:', error)
      }
    },

    async addCity(name: string, limit: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        const response = await axios.post<City>('/cities', {
          name,
          councilor_limit: limit
        })

        this.cities.push(response.data)
        toast.success('City created')
        return true
      } catch (error) {
        console.error('Error adding city:', error)
        return false
      }
    },

    async updateCity(id: number, name: string, limit: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.put<City>(`/cities/${id}`, {
          name,
          councilor_limit: limit
        })

        await this.fetchCities()
        toast.success('City updated')
        return true
      } catch (error) {
        console.error('Error updating city:', error)
        return false
      }
    },

    async deleteCity(id: number): Promise<boolean> {
      try {
        const toast = useToastStore()
        await axios.delete(`/cities/${id}`)
        await this.fetchCities()
        toast.success('City deleted')
        return true
      } catch (error) {
        console.error('Error deleting city:', error)
        return false
      }
    }
  }
})