<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useCityStore } from '../../stores/city'
import { useBallotStore } from '../../stores/ballot'
import { useResultStore } from '../../stores/result'
import type { ResultImportResponse } from '../../types'

const cityStore = useCityStore()
const ballotStore = useBallotStore()
const resultStore = useResultStore()

const selectedCity = ref(0)
const csvFileError = ref('')
const importResponse = ref<ResultImportResponse | null>(null)
const csvFile = ref<File | null>(null)

onMounted(async () => {
  await cityStore.fetchCities()
  await ballotStore.fetchBallots()
})

const totalBallots = computed(() => {
  return ballotStore.ballots.filter(b => b.city_id === selectedCity.value).length
})

const usedBallots = computed(() => {
  return ballotStore.ballots.filter(b => b.city_id === selectedCity.value && b.status === 'used').length
})

const progressPercent = computed(() => {
  if (!totalBallots.value) return 0
  return Math.round((usedBallots.value / totalBallots.value) * 100)
})

const groupedTally = computed(() => {
  const map = new Map<string, typeof resultStore.tally>()
  for (const row of resultStore.tally) {
    if (!map.has(row.position_title)) {
      map.set(row.position_title, [])
    }
    map.get(row.position_title)!.push(row)
  }
  return Array.from(map.entries())
})

const handleCsvChange = (event: Event) => {
  csvFileError.value = ''
  const target = event.target as HTMLInputElement
  csvFile.value = target.files?.[0] || null
}

const importResultsCsv = async () => {
  csvFileError.value = ''
  importResponse.value = null

  if (!csvFile.value) {
    csvFileError.value = 'Please select a CSV file.'
    return
  }

  const response = await resultStore.importResultsCsv(csvFile.value)
  if (response) {
    importResponse.value = response
    await ballotStore.fetchBallots()
    if (selectedCity.value) {
      await resultStore.fetchTally(selectedCity.value)
    }
  } else {
    csvFileError.value = 'Import failed. Check the server logs.'
  }
}

const refreshTally = async () => {
  if (!selectedCity.value) return
  await resultStore.fetchTally(selectedCity.value)
}
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900">Results & Import</h1>
        <p class="text-sm text-zinc-500">Receive votes from Voterra and monitor counts.</p>
      </div>
    </div>

    <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-zinc-900 mb-4">Import Results</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">City</label>
          <select v-model="selectedCity" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm">
            <option :value="0">Select City</option>
            <option v-for="c in cityStore.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Results CSV</label>
          <input @change="handleCsvChange" type="file" accept="text/csv" class="w-full text-sm" />
        </div>
        <div class="md:col-span-2">
          <button
            :disabled="selectedCity === 0"
            @click="importResultsCsv"
            class="w-full border border-zinc-200 text-zinc-700 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
          >
            Import CSV
          </button>
        </div>
      </div>

      <div v-if="csvFileError" class="mt-4 text-sm text-red-600">
        {{ csvFileError }}
      </div>

      <div v-if="importResponse" class="mt-4 text-sm text-zinc-700">
        <div>Processed: <span class="font-semibold">{{ importResponse.processed }}</span></div>
        <div v-if="importResponse.errors.length">Errors:</div>
        <ul v-if="importResponse.errors.length" class="list-disc list-inside text-xs text-red-600">
          <li v-for="(err, idx) in importResponse.errors" :key="idx">{{ err }}</li>
        </ul>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-zinc-200">
        <h3 class="text-xs font-semibold uppercase text-zinc-400">Total Ballots</h3>
        <p class="text-2xl font-bold text-zinc-900 mt-2">{{ totalBallots }}</p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-zinc-200">
        <h3 class="text-xs font-semibold uppercase text-zinc-400">Used Ballots</h3>
        <p class="text-2xl font-bold text-zinc-900 mt-2">{{ usedBallots }}</p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-zinc-200">
        <h3 class="text-xs font-semibold uppercase text-zinc-400">Progress</h3>
        <p class="text-2xl font-bold text-zinc-900 mt-2">{{ progressPercent }}%</p>
      </div>
    </div>

    <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Current Tally</h2>
        <button
          :disabled="selectedCity === 0"
          @click="refreshTally"
          class="text-sm text-zinc-500 hover:text-zinc-900 disabled:opacity-50"
        >
          Refresh
        </button>
      </div>

      <div v-if="selectedCity === 0" class="text-sm text-zinc-400">
        Select a city to view tallies.
      </div>

      <div v-else class="space-y-6">
        <div v-for="[position, rows] in groupedTally" :key="position" class="border border-zinc-100 rounded-xl overflow-hidden">
          <div class="bg-zinc-50 px-4 py-2 text-sm font-semibold text-zinc-700">
            {{ position }}
          </div>
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-zinc-400 bg-white">
              <tr>
                <th class="px-4 py-2">Candidate</th>
                <th class="px-4 py-2">Party</th>
                <th class="px-4 py-2 text-right">Votes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
              <tr v-for="row in rows" :key="row.candidate_id">
                <td class="px-4 py-2 text-zinc-900">{{ row.candidate_name }}</td>
                <td class="px-4 py-2 text-zinc-500">{{ row.party_alias }}</td>
                <td class="px-4 py-2 text-right font-semibold">{{ row.vote_count }}</td>
              </tr>
              <tr v-if="rows.length === 0">
                <td colspan="3" class="px-4 py-6 text-center text-zinc-400">No votes yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
