<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import AdminShell from '../../components/AdminShell.vue'
import { useSetupStore } from '../../stores/setup'
import { useElectionStore } from '../../stores/election'
import type { SetupPayload } from '../../types'

const setupStore = useSetupStore()
const electionStore = useElectionStore()

const jsonFileError = ref('')
const csvFileError = ref('')
const networkError = ref('')
const importUrl = ref('')

const statusCity = computed(() => {
  if (!setupStore.status?.city_name) return 'Not configured'
  return `${setupStore.status.city_name} (#${setupStore.status.city_id})`
})

onMounted(async () => {
  await setupStore.fetchStatus()
})

const importJsonFile = async (event: Event) => {
  jsonFileError.value = ''

  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  try {
    const text = await file.text()
    const payload = JSON.parse(text) as SetupPayload
    const success = await setupStore.importJson(payload)
    if (success) {
      await electionStore.fetchSetup()
    }
  } catch (error) {
    jsonFileError.value = 'Invalid JSON file.'
  }
}

const importCsvFile = async (event: Event) => {
  csvFileError.value = ''

  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  const success = await setupStore.importCsv(file)
  if (success) {
    await electionStore.fetchSetup()
  } else {
    csvFileError.value = 'Failed to import CSV.'
  }
}

const importFromUrl = async () => {
  networkError.value = ''
  if (!importUrl.value) {
    networkError.value = 'Please enter a valid URL.'
    return
  }

  try {
    const response = await axios.get(importUrl.value, { responseType: 'text' })
    const contentType = response.headers['content-type'] || ''

    if (contentType.includes('application/json') || importUrl.value.endsWith('.json')) {
      const payload = JSON.parse(response.data) as SetupPayload
      const success = await setupStore.importJson(payload)
      if (success) {
        await electionStore.fetchSetup()
      }
      return
    }

    const blob = new Blob([response.data], { type: 'text/csv' })
    const success = await setupStore.importCsv(blob)
    if (success) {
      await electionStore.fetchSetup()
    }
  } catch (error) {
    networkError.value = 'Failed to fetch or import the setup file.'
  }
}
</script>

<template>
  <AdminShell>
    <div class="space-y-8">
      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Machine Status</h2>
        <p class="text-sm text-zinc-500 mt-1">Current configuration: <span class="font-semibold text-zinc-900">{{ statusCity }}</span></p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm space-y-4">
          <div>
            <h3 class="text-base font-semibold">Import Setup (JSON)</h3>
            <p class="text-sm text-zinc-500">Use this for network-based setup or JSON file exports.</p>
          </div>
          <input @change="importJsonFile" type="file" accept="application/json" class="text-sm" />
          <p v-if="jsonFileError" class="text-sm text-red-600">{{ jsonFileError }}</p>
        </div>

        <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm space-y-4">
          <div>
            <h3 class="text-base font-semibold">Import Setup (CSV)</h3>
            <p class="text-sm text-zinc-500">Use this for the professor-required CSV workflow.</p>
          </div>
          <input @change="importCsvFile" type="file" accept="text/csv" class="text-sm" />
          <p v-if="csvFileError" class="text-sm text-red-600">{{ csvFileError }}</p>
        </div>
      </div>

      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
          <h3 class="text-base font-semibold">Import via Network</h3>
          <p class="text-sm text-zinc-500">Paste a Countera export URL (JSON or CSV) and import directly.</p>
        </div>
        <div class="flex flex-col md:flex-row gap-3">
          <input
            v-model="importUrl"
            type="text"
            placeholder="http://localhost/acm/counterra/api/cities/1/setup-json"
            class="flex-1 px-4 py-2 border border-zinc-200 rounded-lg text-sm"
          />
          <button @click="importFromUrl" class="px-5 py-2 bg-zinc-900 text-white rounded-lg text-sm font-medium">
            Fetch & Import
          </button>
        </div>
        <p v-if="networkError" class="text-sm text-red-600">{{ networkError }}</p>
      </div>
    </div>
  </AdminShell>
</template>
