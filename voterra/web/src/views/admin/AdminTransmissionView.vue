<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminShell from '../../components/AdminShell.vue'
import { useResultStore } from '../../stores/result'
import { useElectionStore } from '../../stores/election'

const resultStore = useResultStore()
const electionStore = useElectionStore()

const parentEndpoint = ref('http://localhost/acm/counterra/api/results/import?method=3g')
const transmitError = ref('')
const showEndpoint = ref(false)

const exportLocked = computed(() => electionStore.settings?.export_locked === '1')
const exportMethod = computed(() => electionStore.settings?.export_method || '')
const exportKey = computed(() => electionStore.settings?.export_key || '')

onMounted(async () => {
  await electionStore.fetchSetup()
  await resultStore.fetchStats()
  await resultStore.fetchExportLogs()
})

const jsonExportHref = computed(() => (
  'http://localhost/acm/voterra/api/results/export-json?download=1&encrypted=1&method=manual'
))

const transmitToParent = async () => {
  transmitError.value = ''
  if (!parentEndpoint.value) {
    transmitError.value = 'Parent endpoint is required.'
    return
  }
  if (exportLocked.value) {
    transmitError.value = 'Export already completed. Transmission is locked.'
    return
  }

  const success = await resultStore.transmitToParent(parentEndpoint.value)
  if (!success) {
    transmitError.value = 'Transmission failed.'
  } else {
    await electionStore.fetchSetup()
    await resultStore.fetchExportLogs()
  }
}

const refresh = async () => {
  await resultStore.fetchStats()
  await resultStore.fetchExportLogs()
  await electionStore.fetchSetup()
}

const downloadJson = async () => {
  if (exportLocked.value) {
    transmitError.value = 'Export already completed. Download is locked.'
    return
  }
  window.open(jsonExportHref.value, '_blank')
  await electionStore.fetchSetup()
  await resultStore.fetchExportLogs()
}
</script>

<template>
  <AdminShell>
    <div class="space-y-8">
      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Local Election Status</h2>
        <p class="text-sm text-zinc-500">Machine: {{ electionStore.settings?.city_name || 'Not configured' }}</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <div class="border border-zinc-200 rounded-xl p-4">
            <div class="text-xs uppercase text-zinc-400">Total Ballots</div>
            <div class="text-2xl font-semibold">{{ resultStore.stats?.total_ballots ?? 0 }}</div>
          </div>
          <div class="border border-zinc-200 rounded-xl p-4">
            <div class="text-xs uppercase text-zinc-400">Used Ballots</div>
            <div class="text-2xl font-semibold">{{ resultStore.stats?.used_ballots ?? 0 }}</div>
          </div>
          <div class="border border-zinc-200 rounded-xl p-4">
            <div class="text-xs uppercase text-zinc-400">Total Votes</div>
            <div class="text-2xl font-semibold">{{ resultStore.stats?.total_votes ?? 0 }}</div>
          </div>
        </div>
      </div>

      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h2 class="text-lg font-semibold">Transmit Results</h2>
          <button @click="refresh" class="text-sm text-zinc-500 hover:text-zinc-900">Refresh</button>
        </div>
        <div v-if="exportLocked" class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          Export completed via {{ exportMethod || 'manual' }}. Export key: {{ exportKey || 'N/A' }}.
        </div>
        <div class="space-y-3">
          <div class="flex flex-col md:flex-row gap-3">
            <button
              @click="transmitToParent"
              class="px-5 py-2 bg-zinc-900 text-white rounded-lg text-sm font-medium disabled:opacity-50"
              :disabled="exportLocked"
            >
              Transmit (3G)
            </button>
            <button
              @click="downloadJson"
              class="px-5 py-2 border border-zinc-200 rounded-lg text-sm font-medium text-center disabled:opacity-50"
              :disabled="exportLocked"
            >
              Download Encrypted JSON
            </button>
          </div>
          <button
            @click="showEndpoint = !showEndpoint"
            class="text-sm text-zinc-500 hover:text-zinc-900"
          >
            {{ showEndpoint ? 'Hide endpoint' : 'Edit endpoint' }}
          </button>
          <div v-if="showEndpoint" class="flex flex-col md:flex-row gap-3">
            <input
              v-model="parentEndpoint"
              type="text"
              class="flex-1 px-4 py-2 border border-zinc-200 rounded-lg text-sm"
            />
          </div>
          <p v-if="transmitError" class="text-sm text-red-600">{{ transmitError }}</p>
        </div>
      </div>

      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-4">Export Log</h2>
        <div class="overflow-hidden border border-zinc-100 rounded-xl">
          <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 border-b border-zinc-200 text-xs uppercase text-zinc-500 font-semibold">
              <tr>
                <th class="px-4 py-3">Export Key</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Expected</th>
                <th class="px-4 py-3">Exported</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Created</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
              <tr v-if="resultStore.exportLogs.length === 0">
                <td colspan="6" class="px-4 py-4 text-zinc-400">No exports yet.</td>
              </tr>
              <tr v-for="log in resultStore.exportLogs" :key="log.id">
                <td class="px-4 py-3 text-zinc-900">{{ log.export_key }}</td>
                <td class="px-4 py-3 text-zinc-500 uppercase">{{ log.method }}</td>
                <td class="px-4 py-3">{{ log.expected_votes }}</td>
                <td class="px-4 py-3">{{ log.exported_votes }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ log.status }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ log.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminShell>
</template>
