<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useCityStore } from '../../stores/city'
import { usePositionStore } from '../../stores/position'
import { usePartyStore } from '../../stores/party'
import { useCandidateStore } from '../../stores/candidate'
import CandidateModal from './components/CandidateModal.vue'
import type { Candidate } from '../../types'

const cityStore = useCityStore()
const posStore = usePositionStore()
const partyStore = usePartyStore()
const canStore = useCandidateStore()

const search = ref('')
const cityFilter = ref(0)
const posFilter = ref(0)

const isModalOpen = ref(false)
const isEditing = ref(false)
const selectedCandidate = ref<Candidate | null>(null)

onMounted(async () => {
  await cityStore.fetchCities()
  await posStore.fetchPositions()
  await partyStore.fetchParties()
  await canStore.fetchCandidates()
})

const filteredCandidates = computed(() => {
  return canStore.candidates.filter(c => {
    const matchesCity = cityFilter.value === 0 || c.city_id === cityFilter.value
    const matchesPos = posFilter.value === 0 || c.position_id === posFilter.value
    const matchesSearch = c.name.toLowerCase().includes(search.value.toLowerCase())
    return matchesCity && matchesPos && matchesSearch
  })
})

const openAddModal = () => {
  isEditing.value = false
  selectedCandidate.value = null
  isModalOpen.value = true
}

const openEditModal = (candidate: Candidate) => {
  isEditing.value = true
  selectedCandidate.value = candidate
  isModalOpen.value = true
}

const onSave = async (formData: { name: string; city_id: number; position_id: number; party_id: number }) => {
  const payload = {
    name: formData.name,
    position_id: Number(formData.position_id),
    party_id: Number(formData.party_id)
  }

  if (isEditing.value && selectedCandidate.value?.id) {
    await canStore.updateCandidate(selectedCandidate.value.id, payload)
  } else {
    await canStore.addCandidate(payload)
  }

  isModalOpen.value = false
}

const handleDelete = async (id: number | undefined) => {
  if (!id) return
  if (confirm('Are you sure you want to delete this candidate?')) {
    await canStore.deleteCandidate(id)
  }
}
</script>

<template>
  <div class="p-10">
    <div class="flex justify-between items-end mb-8">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900">Candidates</h1>
        <div class="flex gap-4 mt-4">
          <input v-model="search" type="text" placeholder="Search name..." class="border p-2 rounded-lg text-sm w-64" />

          <select v-model="cityFilter" class="border p-2 rounded-lg text-sm">
            <option :value="0">All Cities</option>
            <option v-for="c in cityStore.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>

          <select v-model="posFilter" class="border p-2 rounded-lg text-sm" :disabled="cityFilter === 0">
            <option :value="0">All Positions</option>
            <option v-for="p in posStore.positions.filter(p => p.city_id === cityFilter)" :key="p.id" :value="p.id">
              {{ p.title }}
            </option>
          </select>
        </div>
      </div>

      <button @click="openAddModal" class="bg-black text-white px-6 py-2 rounded-xl text-sm font-medium">
        Add Candidate
      </button>
    </div>

    <div class="bg-white border border-zinc-200 rounded-2xl overflow-hidden shadow-sm">
      <table class="w-full text-left">
        <thead class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase text-zinc-500 font-semibold">
          <tr>
            <th class="px-6 py-4">Candidate Name</th>
            <th class="px-6 py-4">City / Position</th>
            <th class="px-6 py-4">Party</th>
            <th class="px-6 py-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 text-sm">
          <tr v-for="can in filteredCandidates" :key="can.id" class="hover:bg-zinc-50/50">
            <td class="px-6 py-4 font-bold text-zinc-900">{{ can.name }}</td>
            <td class="px-6 py-4">
              <div class="text-zinc-900">{{ can.position_title }}</div>
              <div class="text-xs text-zinc-400">{{ can.city_name }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 bg-zinc-100 rounded text-xs font-mono uppercase">{{ can.party_alias }}</span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="openEditModal(can)" class="text-zinc-400 hover:text-zinc-900 px-2">Edit</button>
              <button @click="handleDelete(can.id)" class="text-zinc-400 hover:text-red-600 px-2">Delete</button>
            </td>
          </tr>
          <tr v-if="filteredCandidates.length === 0">
            <td colspan="4" class="px-6 py-12 text-center text-zinc-400 italic">
              No candidates found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <CandidateModal
      :is-open="isModalOpen"
      :is-editing="isEditing"
      :cities="cityStore.cities"
      :positions="posStore.positions"
      :parties="partyStore.parties"
      :initial-data="selectedCandidate"
      @close="isModalOpen = false"
      @save="onSave"
    />
  </div>
</template>
