<script setup lang="ts">
import { computed, onMounted } from 'vue'
import AdminShell from '../../components/AdminShell.vue'
import { useResultStore } from '../../stores/result'
import { useElectionStore } from '../../stores/election'

const resultStore = useResultStore()
const electionStore = useElectionStore()

onMounted(async () => {
  await electionStore.fetchSetup()
  await resultStore.fetchStats()
  await resultStore.fetchTally()
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

const refresh = async () => {
  await resultStore.fetchStats()
  await resultStore.fetchTally()
}
</script>

<template>
  <AdminShell>
    <div class="space-y-8">
      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">Local Election Status</h2>
            <p class="text-sm text-zinc-500">Machine: {{ electionStore.settings?.city_name || 'Not configured' }}</p>
          </div>
          <button @click="refresh" class="text-sm text-zinc-500 hover:text-zinc-900">Refresh</button>
        </div>
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

      <div class="bg-white border border-zinc-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-zinc-900 mb-2">Local Tally</h2>
        <p class="text-sm text-zinc-500 mb-6">Horizontal bars show relative vote share per position.</p>

        <div v-if="groupedTally.length === 0" class="text-sm text-zinc-400">
          No votes yet.
        </div>

        <div v-else class="space-y-8">
          <div
            v-for="[positionTitle, rows, maxVotes] in groupedTally"
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
  </AdminShell>
</template>
