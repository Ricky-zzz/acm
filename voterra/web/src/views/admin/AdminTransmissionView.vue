<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminShell from '../../components/AdminShell.vue'
import { useResultStore } from '../../stores/result'
import { useElectionStore } from '../../stores/election'

const resultStore = useResultStore()
const electionStore = useElectionStore()

const parentEndpoint = ref('http://localhost/acm/counterra/api/results/import')
const transmitError = ref('')
const exportScope = ref<'untransmitted' | 'all'>('untransmitted')
const showEndpoint = ref(false)

onMounted(async () => {
  await electionStore.fetchSetup()
  await resultStore.fetchStats()
})

const jsonExportHref = computed(() => {
  const scope = exportScope.value
  const mark = scope === 'untransmitted' ? '1' : '0'
  return `http://localhost/acm/voterra/api/results/export-json?scope=${scope}&mark=${mark}&download=1&encrypted=1`
})

const transmitToParent = async () => {
  transmitError.value = ''
  if (!parentEndpoint.value) {
    transmitError.value = 'Parent endpoint is required.'
    return
  }
  const success = await resultStore.transmitToParent(parentEndpoint.value, exportScope.value)
  if (!success) {
    transmitError.value = 'Transmission failed.'
  }
}

const refresh = async () => {
  await resultStore.fetchStats()
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
        <div class="space-y-3">
          <div class="flex flex-col md:flex-row gap-3">
            <select
              v-model="exportScope"
              class="px-4 py-2 border border-zinc-200 rounded-lg text-sm"
            >
              <option value="untransmitted">Untransmitted only</option>
              <option value="all">All (including transmitted)</option>
            </select>
            <button
              @click="transmitToParent"
              class="px-5 py-2 bg-zinc-900 text-white rounded-lg text-sm font-medium"
            >
              Transmit (3G)
            </button>
            <a
              :href="jsonExportHref"
              class="px-5 py-2 border border-zinc-200 rounded-lg text-sm font-medium text-center"
            >
              Download Encrypted JSON
            </a>
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
    </div>
  </AdminShell>
</template>
