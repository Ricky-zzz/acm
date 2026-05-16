<script setup lang="ts">
import { computed, onMounted } from 'vue'
import AdminShell from '../../components/AdminShell.vue'
import { useSetupStore } from '../../stores/setup'
import { useElectionStore } from '../../stores/election'

const setupStore = useSetupStore()
const electionStore = useElectionStore()

const statusCity = computed(() => {
  if (!setupStore.status?.city_name) return 'Not configured'
  return `${setupStore.status.city_name} (#${setupStore.status.city_id})`
})

onMounted(async () => {
  await setupStore.fetchStatus()
})

const wipeMachine = async () => {
  const confirmed = window.confirm(
    'Wipe this Voterra machine? This removes the city setup, authorized ballots, candidates, positions, and local votes.'
  )

  if (!confirmed) return

  const success = await setupStore.wipe()
  if (success) {
    await electionStore.fetchSetup()
  }
}
</script>

<template>
  <AdminShell>
    <div class="space-y-8">
      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Cleanup</h2>
        <p class="text-sm text-zinc-500 mt-1">
          Current configuration:
          <span class="font-semibold text-zinc-900">{{ statusCity }}</span>
        </p>
      </div>

      <div class="bg-white border border-red-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
          <h3 class="text-base font-semibold text-red-700">Wipe Machine</h3>
          <p class="text-sm text-zinc-500">
            Use this before importing a setup file for a different city.
          </p>
        </div>

        <button
          type="button"
          @click="wipeMachine"
          class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-60"
          :disabled="setupStore.loading"
        >
          Wipe
        </button>

        <p v-if="setupStore.lastError" class="text-sm text-red-600">{{ setupStore.lastError }}</p>
        <p v-if="setupStore.lastMessage" class="text-sm text-emerald-700">{{ setupStore.lastMessage }}</p>
      </div>
    </div>
  </AdminShell>
</template>
