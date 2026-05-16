<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useElectionStore } from '../../stores/election'
import { useResultStore } from '../../stores/result'
import type { SetupCandidate } from '../../types'

const electionStore = useElectionStore()
const resultStore = useResultStore()

const step = ref(1)
const ballotNumber = ref('')
const errorMessage = ref('')

const selections = reactive<Record<number, number[]>>({})

onMounted(async () => {
  await Promise.all([
    electionStore.fetchSetup(),
    resultStore.fetchStats()
  ])
})

const cityName = computed(() => electionStore.settings?.city_name || 'Voting Machine')

const candidatesByPosition = computed(() => {
  const map = new Map<number, SetupCandidate[]>()
  for (const candidate of electionStore.candidates) {
    if (!map.has(candidate.position_id)) {
      map.set(candidate.position_id, [])
    }
    map.get(candidate.position_id)!.push(candidate)
  }
  return map
})

const isConfigured = computed(() => {
  return electionStore.positions.length > 0 && electionStore.candidates.length > 0
})

const ballotsAvailable = computed(() => {
  const stats = resultStore.stats
  if (!stats) return null
  return Math.max(stats.total_ballots - stats.used_ballots, 0)
})

const isBallotInventoryExhausted = computed(() => {
  if (!isConfigured.value || ballotsAvailable.value === null) return false
  return ballotsAvailable.value === 0 && resultStore.stats!.total_ballots > 0
})

const selectedChoices = computed(() => {
  return Object.values(selections).flat()
})

const getSelectedCount = (positionId: number) => {
  return selections[positionId]?.length || 0
}

const toggleCandidate = (candidate: SetupCandidate, maxVotes: number) => {
  const list = selections[candidate.position_id] || []
  const idx = list.indexOf(candidate.id)

  if (idx >= 0) {
    list.splice(idx, 1)
    selections[candidate.position_id] = list
    return
  }

  if (list.length >= maxVotes) {
    return
  }

  list.push(candidate.id)
  selections[candidate.position_id] = list
}

const isChecked = (candidate: SetupCandidate) => {
  return (selections[candidate.position_id] || []).includes(candidate.id)
}

const startVoting = async () => {
  errorMessage.value = ''
  const value = ballotNumber.value.trim().toUpperCase()
  if (!value) {
    errorMessage.value = 'Enter your ballot ID.'
    return
  }

  const response = await electionStore.validateBallot(value)
  if (!response) {
    errorMessage.value = 'Unable to validate ballot.'
    return
  }

  if (response.status !== 'valid') {
    errorMessage.value = response.status === 'used' ? 'Ballot already used.' : 'Invalid ballot.'
    return
  }

  ballotNumber.value = value
  step.value = 2
}

const submitVote = async () => {
  errorMessage.value = ''
  if (selectedChoices.value.length === 0) {
    errorMessage.value = 'Select at least one candidate.'
    return
  }

  const success = await electionStore.castVote({
    ballot_number: ballotNumber.value,
    choices: selectedChoices.value
  })

  if (success) {
    step.value = 3
  } else {
    errorMessage.value = 'Unable to record your vote.'
  }
}

const resetMachine = () => {
  ballotNumber.value = ''
  errorMessage.value = ''
  for (const key of Object.keys(selections)) {
    delete selections[Number(key)]
  }
  step.value = 1
}
</script>

<template>
  <div class="min-h-screen bg-zinc-50/50 text-zinc-900">
    <div class="max-w-5xl mx-auto px-6 py-10">
      <header class="text-center space-y-3 mb-10">
        <div class="text-xs uppercase tracking-[0.35em] text-zinc-500">Official Ballot Terminal</div>
        <h1 class="text-4xl md:text-5xl font-bold">{{ cityName }}</h1>
        <p class="text-sm text-zinc-500">Please follow the on-screen instructions to cast your vote.</p>
      </header>

      <div v-if="!isConfigured" class="max-w-xl mx-auto bg-white border border-zinc-200 rounded-2xl p-8 text-center space-y-4 shadow-sm">
        <div class="text-3xl font-bold">Machine Not Ready</div>
        <p class="text-lg">This voting machine has not been configured yet.</p>
        <p class="text-sm text-zinc-500">Please contact the admin to import the setup file before any voter can proceed.</p>
      </div>

      <div v-else-if="isBallotInventoryExhausted" class="max-w-xl mx-auto bg-white border border-zinc-200 rounded-2xl p-8 text-center space-y-4 shadow-sm">
        <div class="text-3xl font-bold">Ballot Inventory Exhausted</div>
        <p class="text-lg">All authorized ballots have already been used.</p>
        <p class="text-sm text-zinc-500">Import more ballots from the admin screen before accepting the next voter.</p>
        <div class="pt-2 text-xs uppercase tracking-[0.2em] text-zinc-500">
          Remaining Ballots: {{ ballotsAvailable }}
        </div>
      </div>

      <div v-else-if="step === 1" class="max-w-xl mx-auto bg-white border border-zinc-200 rounded-2xl p-8 shadow-sm">
        <h2 class="text-2xl font-semibold mb-4">Step 1: Enter Ballot ID</h2>
        <input
          v-model="ballotNumber"
          type="text"
          placeholder="BALLOT-ID"
          class="w-full border border-zinc-300 rounded-lg px-4 py-3 text-lg uppercase tracking-wider bg-white"
          :disabled="!isConfigured"
        />
        <button
          @click="startVoting"
          class="w-full mt-6 bg-zinc-900 text-white text-lg py-3 rounded-lg disabled:opacity-50"
          :disabled="!isConfigured"
        >
          Continue
        </button>
        <p v-if="errorMessage" class="mt-4 text-red-600 font-semibold">{{ errorMessage }}</p>
      </div>

      <div v-else-if="step === 2" class="space-y-6">
        <div class="bg-white border border-zinc-200 px-6 py-4 rounded-2xl flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="text-xs uppercase text-zinc-500">Ballot ID</div>
            <div class="text-xl font-semibold tracking-wider text-zinc-900">{{ ballotNumber }}</div>
          </div>
          <button @click="resetMachine" class="border border-zinc-300 px-4 py-2 text-sm rounded-lg text-zinc-700">Cancel</button>
        </div>

        <div class="space-y-6">
          <div
            v-for="position in electionStore.positions"
            :key="position.id"
            class="border border-zinc-200 rounded-2xl p-6 bg-white"
          >
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-semibold">{{ position.title }}</h3>
              <span class="text-sm uppercase text-zinc-500">Select {{ position.max_votes }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label
                v-for="candidate in candidatesByPosition.get(position.id) || []"
                :key="candidate.id"
                class="flex items-center gap-3 border border-zinc-200 rounded-lg px-4 py-3 text-lg cursor-pointer bg-white hover:border-zinc-300"
              >
                <input
                  type="checkbox"
                  class="h-5 w-5 accent-zinc-900"
                  :checked="isChecked(candidate)"
                  :disabled="!isChecked(candidate) && getSelectedCount(position.id) >= position.max_votes"
                  @change="toggleCandidate(candidate, position.max_votes)"
                />
                <span>{{ candidate.name }}</span>
                <span class="text-xs uppercase text-zinc-500">{{ candidate.party_alias }}</span>
              </label>
            </div>
            <div class="mt-3 text-xs uppercase text-zinc-500">Selected: {{ getSelectedCount(position.id) }}/{{ position.max_votes }}</div>
          </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
          <button @click="resetMachine" class="border border-zinc-300 px-4 py-3 text-lg rounded-lg text-zinc-700">Back</button>
          <button @click="submitVote" class="flex-1 bg-zinc-900 text-white px-4 py-3 text-lg rounded-lg">Submit Vote</button>
        </div>
        <p v-if="errorMessage" class="text-red-600 font-semibold">{{ errorMessage }}</p>
      </div>

      <div v-else class="max-w-xl mx-auto bg-white border border-zinc-200 rounded-2xl p-8 text-center space-y-4 shadow-sm">
        <div class="text-3xl font-bold">Vote Recorded</div>
        <p class="text-lg">Thank you for participating.</p>
        <button @click="resetMachine" class="mt-4 bg-zinc-900 text-white px-6 py-3 text-lg rounded-lg">Next Voter</button>
      </div>
    </div>
  </div>
</template>
