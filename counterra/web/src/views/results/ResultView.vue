<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useCityStore } from '../../stores/city'
import { useBallotStore } from '../../stores/ballot'
import { useResultStore } from '../../stores/result'
import type { EncryptedEnvelope, ResultImportPayload, ResultImportResponse } from '../../types'

const cityStore = useCityStore()
const ballotStore = useBallotStore()
const resultStore = useResultStore()

const activeTab = ref<'import' | 'results'>('import')
const selectedCity = ref(0)
const selectedPosition = ref<string>('__ALL__')
const jsonFileError = ref('')
const importResponse = ref<ResultImportResponse | null>(null)
const jsonPayload = ref<ResultImportPayload | EncryptedEnvelope | null>(null)
const logMethod = ref<'manual' | '3g'>('manual')

onMounted(async () => {
  await cityStore.fetchCities()
  await ballotStore.fetchBallots()
  await resultStore.fetchImportLogs(logMethod.value)
})

watch(
  () => ({ tab: activeTab.value, cityId: selectedCity.value }),
  async ({ tab, cityId }) => {
    if (tab !== 'results') return
    if (!cityId) return
    await resultStore.fetchTally(cityId)
  }
)

watch(
  () => logMethod.value,
  async (method) => {
    await resultStore.fetchImportLogs(method)
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

const handleJsonChange = async (event: Event) => {
  jsonFileError.value = ''
  jsonPayload.value = null

  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  try {
    const text = await file.text()
    const parsed = JSON.parse(text) as unknown
    if (!parsed || typeof parsed !== 'object') {
      jsonFileError.value = 'Invalid JSON file.'
      return
    }

    const maybeEnv = parsed as Partial<EncryptedEnvelope>
    const isEnvelope =
      maybeEnv.v === 1 &&
      maybeEnv.alg === 'A256GCM' &&
      typeof maybeEnv.iv === 'string' &&
      typeof maybeEnv.tag === 'string' &&
      typeof maybeEnv.ct === 'string'

    if (isEnvelope) {
      jsonPayload.value = parsed as EncryptedEnvelope
      return
    }

    jsonFileError.value = 'Encrypted results JSON required.'
    return
  } catch {
    jsonFileError.value = 'Invalid JSON file.'
  }
}

const importResultsJson = async () => {
  jsonFileError.value = ''
  importResponse.value = null

  if (!jsonPayload.value) {
    jsonFileError.value = 'Please select a valid results JSON file.'
    return
  }

  const response = await resultStore.importResults(jsonPayload.value, 'manual')
  if (response) {
    importResponse.value = response
    await ballotStore.fetchBallots()
    await resultStore.fetchImportLogs(logMethod.value)
  } else {
    jsonFileError.value = 'Import failed. Check the server logs.'
  }
}

const refreshTally = async () => {
  if (!selectedCity.value) return
  await resultStore.fetchTally(selectedCity.value)
}

const approveLog = async (id: number) => {
  const success = await resultStore.updateImportLogStatus(id, 'accepted')
  if (success) {
    await resultStore.fetchImportLogs(logMethod.value)
  }
}

const rejectLog = async (id: number) => {
  const success = await resultStore.updateImportLogStatus(id, 'rejected')
  if (success) {
    await resultStore.fetchImportLogs(logMethod.value)
  }
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
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">Import Results (Encrypted JSON)</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Encrypted Results JSON</label>
            <input @change="handleJsonChange" type="file" accept="application/json" class="w-full text-sm" />
          </div>
          <div class="md:col-span-2">
            <button
              @click="importResultsJson"
              class="w-full border border-zinc-200 text-zinc-700 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
              :disabled="!jsonPayload"
            >
              Import Encrypted JSON
            </button>
          </div>
        </div>

        <div v-if="jsonFileError" class="mt-4 text-sm text-red-600">
          {{ jsonFileError }}
        </div>

        <div v-if="importResponse" class="mt-4 text-sm text-zinc-700">
          <div>Processed: <span class="font-semibold">{{ importResponse.processed }}</span></div>
          <div v-if="importResponse.errors.length">Errors:</div>
          <ul v-if="importResponse.errors.length" class="list-disc list-inside text-xs text-red-600">
            <li v-for="(err, idx) in importResponse.errors" :key="idx">{{ err }}</li>
          </ul>
        </div>
      </div>

      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between gap-4">
          <h2 class="text-lg font-semibold text-zinc-900">Import Log</h2>
          <div class="inline-flex items-center gap-2 bg-zinc-100 rounded-full p-1">
            <button
              @click="logMethod = 'manual'"
              class="px-4 py-2 text-sm rounded-full"
              :class="logMethod === 'manual' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-900'"
            >
              Manual
            </button>
            <button
              @click="logMethod = '3g'"
              class="px-4 py-2 text-sm rounded-full"
              :class="logMethod === '3g' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-900'"
            >
              3G
            </button>
          </div>
        </div>
        <div class="overflow-hidden border border-zinc-100 rounded-xl">
          <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase text-zinc-500 font-semibold">
              <tr>
                <th class="px-4 py-3">City</th>
                <th class="px-4 py-3">Import Key</th>
                <th class="px-4 py-3">Expected</th>
                <th class="px-4 py-3">Received</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Transmission</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
              <tr v-if="resultStore.importLogs.length === 0">
                <td colspan="7" class="px-4 py-4 text-zinc-400">No imports yet.</td>
              </tr>
              <tr v-for="log in resultStore.importLogs" :key="log.id">
                <td class="px-4 py-3 text-zinc-900">{{ log.city_name }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ log.import_key }}</td>
                <td class="px-4 py-3">{{ log.expected_votes }}</td>
                <td class="px-4 py-3">{{ log.received_votes }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ log.status }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ log.note || 'n/a' }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <button
                      @click="approveLog(log.id)"
                      class="px-3 py-1 text-xs rounded-md border border-zinc-200 text-zinc-700 hover:border-zinc-400"
                    >
                      Approve
                    </button>
                    <button
                      @click="rejectLog(log.id)"
                      class="px-3 py-1 text-xs rounded-md border border-red-200 text-red-600 hover:border-red-400"
                    >
                      Deny
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
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
