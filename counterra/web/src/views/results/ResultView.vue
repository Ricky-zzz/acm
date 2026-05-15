<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useCityStore } from '../../stores/city'
import { useBallotStore } from '../../stores/ballot'
import { useResultStore } from '../../stores/result'
import type { ResultImportResponse } from '../../types'

const cityStore = useCityStore()
const ballotStore = useBallotStore()
const resultStore = useResultStore()

const activeTab = ref<'import' | 'results'>('import')
const selectedCity = ref(0)
const selectedPosition = ref<string>('__ALL__')
const csvFileError = ref('')
const importResponse = ref<ResultImportResponse | null>(null)
const csvFile = ref<File | null>(null)

onMounted(async () => {
  await cityStore.fetchCities()
  await ballotStore.fetchBallots()
})

watch(
  () => ({ tab: activeTab.value, cityId: selectedCity.value }),
  async ({ tab, cityId }) => {
    if (tab !== 'results') return
    if (!cityId) return
    await resultStore.fetchTally(cityId)
  }
)

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
  return Array.from(map.entries()).map(([positionTitle, rows]) => {
    const sorted = [...rows].sort((a, b) => {
      if (b.vote_count !== a.vote_count) return b.vote_count - a.vote_count
      return a.candidate_name.localeCompare(b.candidate_name)
    })
    const maxVotes = sorted.reduce((acc, r) => Math.max(acc, r.vote_count), 0)
    return [positionTitle, sorted, maxVotes] as const
  })
})

const positionOptions = computed(() => {
  const titles = new Set<string>()
  for (const row of resultStore.tally) titles.add(row.position_title)
  return Array.from(titles.values()).sort((a, b) => a.localeCompare(b))
})

const filteredGroupedTally = computed(() => {
  if (selectedPosition.value === '__ALL__') return groupedTally.value
  return groupedTally.value.filter(([title]) => title === selectedPosition.value)
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

    <div class="bg-white border border-zinc-200 rounded-2xl p-2 shadow-sm">
      <div class="flex gap-2">
        <button
          @click="activeTab = 'import'"
          class="flex-1 py-2 rounded-xl text-sm font-semibold transition-colors"
          :class="activeTab === 'import' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
        >
          Import
        </button>
        <button
          @click="activeTab = 'results'"
          class="flex-1 py-2 rounded-xl text-sm font-semibold transition-colors"
          :class="activeTab === 'results' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
        >
          Results
        </button>
      </div>
    </div>

    <div v-if="activeTab === 'import'" class="space-y-6">
      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">Import Results (SD Card Simulation)</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Results CSV</label>
            <input @change="handleCsvChange" type="file" accept="text/csv" class="w-full text-sm" />
          </div>
          <div class="md:col-span-2">
            <button
              @click="importResultsCsv"
              class="w-full border border-zinc-200 text-zinc-700 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
              :disabled="!csvFile"
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
    </div>

    <div v-else class="space-y-6">
      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div class="flex flex-wrap gap-4 items-end">
            <div>
              <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">City</label>
              <select v-model="selectedCity" class="w-64 px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm">
                <option :value="0">Select City</option>
                <option v-for="c in cityStore.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Positions</label>
              <select
                v-model="selectedPosition"
                class="w-64 px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm"
                :disabled="selectedCity === 0"
              >
                <option value="__ALL__">All positions</option>
                <option v-for="title in positionOptions" :key="title" :value="title">{{ title }}</option>
              </select>
            </div>
          </div>

          <button
            :disabled="selectedCity === 0"
            @click="refreshTally"
            class="text-sm text-zinc-500 hover:text-zinc-900 disabled:opacity-50"
          >
            Refresh
          </button>
        </div>
      </div>

      <div v-if="selectedCity" class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
        <h2 class="text-lg font-semibold text-zinc-900 mb-2">Live Tally</h2>
        <p class="text-sm text-zinc-500 mb-6">Horizontal bars show relative vote share per position.</p>

        <div v-if="selectedCity === 0" class="text-sm text-zinc-400">
          Select a city to view results.
        </div>

        <div v-else-if="filteredGroupedTally.length === 0" class="text-sm text-zinc-400">
          No results loaded yet.
        </div>

        <div v-else class="space-y-8">
          <div
            v-for="[positionTitle, rows, maxVotes] in filteredGroupedTally"
            :key="positionTitle"
            class="border border-zinc-100 rounded-2xl overflow-hidden"
          >
            <div class="bg-zinc-50 px-5 py-3 flex items-center justify-between">
              <div class="text-sm font-semibold text-zinc-800">{{ positionTitle }}</div>
              <div class="text-xs text-zinc-500">Top votes: {{ maxVotes }}</div>
            </div>

            <div class="p-5 space-y-3">
              <div
                v-for="row in rows"
                :key="row.candidate_id"
                class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center"
              >
                <div class="md:col-span-4">
                  <div class="text-sm font-semibold text-zinc-900">{{ row.candidate_name }}</div>
                  <div class="text-xs uppercase text-zinc-500">{{ row.party_alias }}</div>
                </div>

                <div class="md:col-span-7">
                  <div class="w-full h-3 bg-zinc-100 rounded-full overflow-hidden">
                    <div
                      class="h-3 bg-zinc-900"
                      :style="{ width: `${maxVotes ? Math.round((row.vote_count / maxVotes) * 100) : 0}%` }"
                    />
                  </div>
                </div>

                <div class="md:col-span-1 text-right text-sm font-bold text-zinc-900">
                  {{ row.vote_count }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
