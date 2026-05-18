<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminShell from '../../components/AdminShell.vue'
import { useSetupStore } from '../../stores/setup'
import { useElectionStore } from '../../stores/election'
import type { EncryptedEnvelope, SetupPayload } from '../../types'

const setupStore = useSetupStore()
const electionStore = useElectionStore()

const jsonFileError = ref('')
const selectedFileName = ref('')

const isConfigured = computed(() => Boolean(setupStore.status?.city_id))

const statusCity = computed(() => {
  if (!setupStore.status?.city_name) return 'Not configured'
  return `${setupStore.status.city_name} (#${setupStore.status.city_id})`
})

const importTitle = computed(() => 'Import Setup (JSON)')

onMounted(async () => {
  await setupStore.fetchStatus()
})

const importJsonFile = async (event: Event) => {
  jsonFileError.value = ''

  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  selectedFileName.value = file?.name ?? ''
  if (!file) return

  try {
    setupStore.lastError = ''
    setupStore.lastMessage = ''
    const text = await file.text()
    const payload = JSON.parse(text) as SetupPayload | EncryptedEnvelope
    const isEnvelope =
      (payload as EncryptedEnvelope).v === 1 &&
      (payload as EncryptedEnvelope).alg === 'A256GCM' &&
      typeof (payload as EncryptedEnvelope).iv === 'string' &&
      typeof (payload as EncryptedEnvelope).tag === 'string' &&
      typeof (payload as EncryptedEnvelope).ct === 'string'

    if (!isEnvelope) {
      jsonFileError.value = 'Encrypted setup JSON required.'
      return
    }

    const success = await setupStore.importJson(payload as EncryptedEnvelope)
    if (success) {
      await electionStore.fetchSetup()
      window.open('http://localhost/acm/voterra/api/results/return-pdf', '_blank')
    }
  } catch (error) {
    jsonFileError.value = 'Invalid JSON file.'
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
        <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm space-y-4 lg:col-span-2">
          <div>
            <h3 class="text-base font-semibold">{{ importTitle }}</h3>
            <p class="text-sm text-zinc-500">
              Encrypted JSON required to initialize this machine. After configuration, ballot imports are locked.
            </p>
          </div>
          <div v-if="!isConfigured" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <label
              for="setup-json-file"
              class="inline-flex items-center justify-center px-4 py-2 border border-zinc-300 rounded-lg text-sm font-medium text-zinc-700 bg-white hover:bg-zinc-50 cursor-pointer"
            >
              Choose JSON file
            </label>
            <span class="text-sm text-zinc-500">{{ selectedFileName || 'No file selected' }}</span>
            <input
              id="setup-json-file"
              @change="importJsonFile"
              type="file"
              accept="application/json"
              class="sr-only"
            />
          </div>
          <div v-else class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Machine configured. Ballot imports are locked. Use Cleanup -> Wipe to reconfigure.
          </div>
          <p v-if="jsonFileError" class="text-sm text-red-600">{{ jsonFileError }}</p>
          <p v-if="setupStore.lastError" class="text-sm text-red-600">{{ setupStore.lastError }}</p>
          <p v-if="setupStore.lastMessage" class="text-sm text-emerald-700">{{ setupStore.lastMessage }}</p>
        </div>
      </div>
    </div>
  </AdminShell>
</template>
