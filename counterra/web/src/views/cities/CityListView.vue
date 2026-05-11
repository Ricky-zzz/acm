<script setup lang="ts">
import { onMounted, ref, reactive, computed } from 'vue'
import { useCityStore } from '../../stores/city'
import type { City } from '../../types'

const cityStore = useCityStore()
const isModalOpen = ref(false)
const isEditing = ref(false)
const searchQuery = ref('')
const selectedId = ref<number | null>(null)

const form = reactive({
  name: '',
  limit: 12
})

// This replaces your v-for target
const filteredCities = computed(() => {
  return cityStore.cities.filter(city => 
    city.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

onMounted(() => {
  cityStore.fetchCities()
})

const openAddModal = () => {
  isEditing.value = false
  selectedId.value = null
  form.name = ''
  form.limit = 12
  isModalOpen.value = true
}

const openEditModal = (city: City) => {
  isEditing.value = true
  selectedId.value = city.id || null
  form.name = city.name
  form.limit = city.councilor_limit
  isModalOpen.value = true
}

const submitForm = async () => {
  if (isEditing.value && selectedId.value) {
    const success = await cityStore.updateCity(selectedId.value, form.name, form.limit)
    if (success) {
      isModalOpen.value = false
      form.name = ''
      form.limit = 12
    }
  } else {
    const success = await cityStore.addCity(form.name, form.limit)
    if (success) {
      isModalOpen.value = false
      form.name = ''
      form.limit = 12
    }
  }
}

const handleDelete = async (id: number | undefined) => {
  if (!id) return
  if (confirm('Are you sure? This will also delete all positions and candidates in this city.')) {
    await cityStore.deleteCity(id)
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-semibold text-zinc-900">Cities</h1>
        <p class="text-sm text-zinc-500">Manage voting jurisdictions and council limits.</p>
      </div>
      <button 
        @click="openAddModal"
        class="bg-zinc-900 hover:bg-black text-white text-sm px-4 py-2 rounded-lg transition-all"
      >
        Add City
      </button>
    </div>

    <!-- Search Bar (ChatGPT Style) -->
    <div class="mb-6">
      <div class="relative max-w-sm">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </span>
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Search cities..." 
          class="w-full pl-10 pr-4 py-2 bg-white border border-zinc-200 rounded-lg text-sm focus:ring-1 focus:ring-black outline-none"
        />
      </div>
    </div>

    <!-- City Table -->
    <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-sm">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 font-semibold">
            <th class="px-6 py-4">City Name</th>
            <th class="px-6 py-4">Councilor Limit</th>
            <th class="px-6 py-4">Date Created</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="text-sm text-zinc-600 divide-y divide-zinc-100">
          <tr v-for="city in filteredCities" :key="city.id" class="hover:bg-zinc-50/50 transition-colors">
            <td class="px-6 py-4 font-medium text-zinc-900">{{ city.name }}</td>
            <td class="px-6 py-4">
              <span class="bg-zinc-100 px-2 py-1 rounded text-xs font-mono">{{ city.councilor_limit }} Slots</span>
            </td>
            <td class="px-6 py-4 text-zinc-400">{{ city.created_at }}</td>
            <td class="px-6 py-4 text-right">
              <button @click="openEditModal(city)" class="text-zinc-400 hover:text-zinc-900 px-2">Edit</button>
              <button @click="handleDelete(city.id)" class="text-zinc-400 hover:text-red-600 px-2">Delete</button>
            </td>
          </tr>
          <tr v-if="filteredCities.length === 0">
            <td colspan="4" class="px-6 py-12 text-center text-zinc-400 italic">
              No cities registered in the system.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Simple Modal for Adding City -->
    <div v-if="isModalOpen" class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-xl border border-zinc-200">
        <div class="p-6 border-b border-zinc-100">
          <h3 class="text-lg font-semibold">{{ isEditing ? 'Edit City' : 'Register New City' }}</h3>
        </div>
        <form @submit.prevent="submitForm" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase mb-2">City Name</label>
            <input v-model="form.name" type="text" required class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none focus:border-zinc-900 text-sm" placeholder="e.g. Quezon City" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase mb-2">Councilor Limit</label>
            <input v-model="form.limit" type="number" required class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none focus:border-zinc-900 text-sm" />
          </div>
          <div class="flex gap-3 mt-6">
            <button type="button" @click="isModalOpen = false" class="flex-1 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50 rounded-lg transition-all">Cancel</button>
            <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium bg-zinc-900 text-white rounded-lg hover:bg-black transition-all shadow-md">{{ isEditing ? 'Update Changes' : 'Save City' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>