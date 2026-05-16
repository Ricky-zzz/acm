<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useCityStore } from '../../stores/city'
import { useBallotStore, type BallotStatusFilter } from '../../stores/ballot'
import type { BallotSummary } from '../../types'

const cityStore = useCityStore()
const ballotStore = useBallotStore()

const generationCity = ref(0)
const inventoryCity = ref(0)
const quantity = ref(10)
const statusFilter = ref<BallotStatusFilter>('all')
const search = ref('')

onMounted(async () => {
  await cityStore.fetchCities()
  await ballotStore.fetchBallots()
})

const filteredBallots = computed(() => {
  return ballotStore.ballots.filter(b => {
    const matchesStatus = statusFilter.value === 'all' || b.status === statusFilter.value
    const matchesCity = inventoryCity.value === 0 || b.city_id === inventoryCity.value
    const matchesSearch = b.ballot_number.toLowerCase().includes(search.value.toLowerCase())
    return matchesStatus && matchesCity && matchesSearch
  })
})

const inventorySummary = computed<BallotSummary[]>(() => {
  const map = new Map<number, BallotSummary>()

  for (const city of cityStore.cities) {
    map.set(city.id || 0, {
      city_id: city.id || 0,
      city_name: city.name,
      total: 0,
      unused: 0,
      used: 0
    })
  }

  for (const ballot of ballotStore.ballots) {
    const key = ballot.city_id
    if (!map.has(key)) {
      map.set(key, {
        city_id: key,
        city_name: ballot.city_name || 'Unknown',
        total: 0,
        unused: 0,
        used: 0
      })
    }

    const entry = map.get(key)!
    entry.total += 1
    if (ballot.status === 'unused') {
      entry.unused += 1
    } else {
      entry.used += 1
    }
  }

  return Array.from(map.values()).filter(item => item.city_id > 0)
})

const generateBallots = async () => {
  if (!generationCity.value || quantity.value <= 0) return
  await ballotStore.generateBallots(generationCity.value, quantity.value)
}

const printPdf = (cityId: number) => {
  if (!cityId) return
  window.open(`http://localhost/acm/counterra/api/cities/${cityId}/print`, '_blank')
}

const exportSetupJson = (cityId: number) => {
  if (!cityId) return
  window.open(`http://localhost/acm/counterra/api/cities/${cityId}/setup-json?download=1&encrypted=1`, '_blank')
}
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900">Ballot Management</h1>
        <p class="text-sm text-zinc-500">Generate secure ballots and manage inventory.</p>
      </div>
    </div>

    <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-zinc-900 mb-4">Generate Ballots</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">City</label>
          <select v-model="generationCity" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm">
            <option :value="0">Select City</option>
            <option v-for="c in cityStore.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Quantity</label>
          <input v-model.number="quantity" type="number" min="1" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm" />
        </div>
        <div>
          <button
            :disabled="generationCity === 0"
            @click="generateBallots"
            class="w-full bg-zinc-900 text-white py-2 rounded-lg text-sm font-medium disabled:opacity-50"
          >
            Generate Ballots
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Inventory</h2>
        <div class="flex flex-wrap gap-3">
          <input v-model="search" type="text" placeholder="Search ballot ID..." class="border p-2 rounded-lg text-sm w-56" />
          <select v-model="statusFilter" class="border p-2 rounded-lg text-sm">
            <option value="all">All</option>
            <option value="unused">Unused</option>
            <option value="used">Used</option>
          </select>
          <select v-model="inventoryCity" class="border p-2 rounded-lg text-sm">
            <option :value="0">All Cities</option>
            <option v-for="c in cityStore.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
      </div>

      <div class="overflow-hidden border border-zinc-100 rounded-xl">
        <table class="w-full text-left">
          <thead class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase text-zinc-500 font-semibold">
            <tr>
              <th class="px-6 py-3">Ballot ID</th>
              <th class="px-6 py-3">City</th>
              <th class="px-6 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 text-sm">
            <tr v-for="ballot in filteredBallots" :key="ballot.id">
              <td class="px-6 py-3 font-mono text-xs">{{ ballot.ballot_number }}</td>
              <td class="px-6 py-3">{{ ballot.city_name }}</td>
              <td class="px-6 py-3">
                <span
                  class="px-2 py-1 rounded text-xs font-semibold"
                  :class="ballot.status === 'unused' ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-600'"
                >
                  {{ ballot.status }}
                </span>
              </td>
            </tr>
            <tr v-if="filteredBallots.length === 0">
              <td colspan="3" class="px-6 py-10 text-center text-zinc-400 italic">
                No ballots found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Print Ballots</h2>
        <span class="text-xs text-zinc-400">Uses unused ballot IDs only</span>
      </div>
      <div class="overflow-hidden border border-zinc-100 rounded-xl">
        <table class="w-full text-left">
          <thead class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase text-zinc-500 font-semibold">
            <tr>
              <th class="px-6 py-3">City</th>
              <th class="px-6 py-3">Total</th>
              <th class="px-6 py-3">Unused</th>
              <th class="px-6 py-3">Used</th>
              <th class="px-6 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 text-sm">
            <tr v-for="row in inventorySummary" :key="row.city_id">
              <td class="px-6 py-3 font-medium text-zinc-900">{{ row.city_name }}</td>
              <td class="px-6 py-3">{{ row.total }}</td>
              <td class="px-6 py-3">{{ row.unused }}</td>
              <td class="px-6 py-3">{{ row.used }}</td>
              <td class="px-6 py-3 text-right">
                <button
                  @click="printPdf(row.city_id)"
                  class="text-zinc-500 hover:text-zinc-900 px-2"
                  :disabled="row.unused === 0"
                >
                  Print PDF
                </button>
                <button
                  @click="exportSetupJson(row.city_id)"
                  class="text-zinc-500 hover:text-zinc-900 px-2"
                  :disabled="row.total === 0"
                >
                  Export Setup (Encrypted JSON)
                </button>
              </td>
            </tr>
            <tr v-if="inventorySummary.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-zinc-400 italic">
                No ballots generated yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
