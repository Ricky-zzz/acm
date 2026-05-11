<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { usePositionStore } from '../../stores/position'
import { useCityStore } from '../../stores/city'
import type { Position } from '../../types'

const posStore = usePositionStore()
const cityStore = useCityStore()

const isModalOpen = ref(false)
const isEditing = ref(false)
const errorMessage = ref('')
const selectedId = ref<number | null>(null)
const form = reactive({
  title: '',
  max_votes: 1,
  city_id: 0
})

onMounted(async () => {
  await posStore.fetchPositions()
  
  // REUSE LOGIC: 
  // If the cities aren't loaded yet (e.g. user refreshed on this page), fetch them.
  if (cityStore.cities.length === 0) {
    await cityStore.fetchCities()
  }
})

const openAddModal = () => {
  isEditing.value = false
  selectedId.value = null
  errorMessage.value = ''
  form.title = ''
  form.max_votes = 1
  form.city_id = 0
  isModalOpen.value = true
}

const openEditModal = (position: Position) => {
  isEditing.value = true
  selectedId.value = position.id || null
  errorMessage.value = ''
  form.title = position.title
  form.max_votes = position.max_votes
  form.city_id = position.city_id
  isModalOpen.value = true
}

const submitForm = async () => {
  errorMessage.value = ''
  
  if (!form.title || !form.city_id) {
    errorMessage.value = 'Please fill in all fields'
    return
  }

  if (isEditing.value && selectedId.value) {
    const success = await posStore.updatePosition(selectedId.value, { 
      ...form, 
      city_id: Number(form.city_id),
      id: selectedId.value 
    })
    if (success) {
      isModalOpen.value = false
      form.title = ''
      form.max_votes = 1
      form.city_id = 0
    } else {
      errorMessage.value = 'Failed to update position. Please check your input.'
    }
  } else {
    const success = await posStore.addPosition({ ...form, city_id: Number(form.city_id) })
    if (success) {
      isModalOpen.value = false
      form.title = ''
      form.max_votes = 1
      form.city_id = 0
    } else {
      errorMessage.value = 'Failed to create position. Please check your input.'
    }
  }
}

const handleDelete = async (id: number | undefined) => {
  if (!id) return
  if (confirm('Are you sure you want to delete this position?')) {
    await posStore.deletePosition(id)
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-semibold text-zinc-900">Positions</h1>
        <p class="text-sm text-zinc-500">Manage voting positions for each city.</p>
      </div>
      <button 
        @click="openAddModal"
        class="bg-zinc-900 hover:bg-black text-white text-sm px-4 py-2 rounded-lg transition-all"
      >
        Add Position
      </button>
    </div>

    <!-- Positions Table -->
    <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-sm">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 font-semibold">
            <th class="px-6 py-4">City</th>
            <th class="px-6 py-4">Position Title</th>
            <th class="px-6 py-4">Max Votes Allowed</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="text-sm text-zinc-600 divide-y divide-zinc-100">
          <tr v-for="pos in posStore.positions" :key="pos.id" class="hover:bg-zinc-50/50 transition-colors">
            <td class="px-6 py-4 font-medium text-zinc-900">{{ pos.city_name }}</td>
            <td class="px-6 py-4 font-medium">{{ pos.title }}</td>
            <td class="px-6 py-4">
              <span class="bg-zinc-100 px-2 py-1 rounded text-xs font-mono">{{ pos.max_votes }} vote(s)</span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="openEditModal(pos)" class="text-zinc-400 hover:text-zinc-900 px-2">Edit</button>
              <button @click="handleDelete(pos.id)" class="text-zinc-400 hover:text-red-600 px-2">Delete</button>
            </td>
          </tr>
          <tr v-if="posStore.positions.length === 0">
            <td colspan="4" class="px-6 py-12 text-center text-zinc-400 italic">
              No positions registered in the system.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal for Adding Position -->
    <div v-if="isModalOpen" class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-xl border border-zinc-200">
        <div class="p-6 border-b border-zinc-100">
          <h3 class="text-lg font-semibold">{{ isEditing ? 'Edit Position' : 'Add New Position' }}</h3>
        </div>
        <form @submit.prevent="submitForm" class="p-6 space-y-4">
          
          <!-- Error Message -->
          <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600">
            {{ errorMessage }}
          </div>
          
          <!-- CITY DROPDOWN (Reusing City Store!) -->
          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase mb-2">Target City</label>
            <select 
              v-model="form.city_id" 
              required 
              class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none focus:border-zinc-900 text-sm"
            >
              <option :value="0" disabled>Select a city</option>
              <option v-for="city in cityStore.cities" :key="city.id" :value="city.id">
                {{ city.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase mb-2">Position Title</label>
            <input 
              v-model="form.title" 
              type="text" 
              placeholder="e.g. Mayor, Councilor, Vice Mayor" 
              required 
              class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none focus:border-zinc-900 text-sm" 
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase mb-2">Max Votes Allowed</label>
            <input 
              v-model="form.max_votes" 
              type="number" 
              min="1" 
              required 
              class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none focus:border-zinc-900 text-sm" 
            />
          </div>

          <div class="flex gap-3 mt-6">
            <button type="button" @click="isModalOpen = false" class="flex-1 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50 rounded-lg transition-all">Cancel</button>
            <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium bg-zinc-900 text-white rounded-lg hover:bg-black transition-all shadow-md">{{ isEditing ? 'Update Changes' : 'Save Position' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
