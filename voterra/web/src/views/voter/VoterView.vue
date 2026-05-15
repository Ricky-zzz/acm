<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useElectionStore } from '../../stores/election'
import type { SetupCandidate } from '../../types'

const electionStore = useElectionStore()

const step = ref(1)
const ballotNumber = ref('')
const errorMessage = ref('')

const selections = reactive<Record<number, number[]>>({})

onMounted(async () => {
  await electionStore.fetchSetup()
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
  <div class="min-h-screen bg-white text-black">
    <div class="max-w-5xl mx-auto px-6 py-10">
      <header class="text-center space-y-3 mb-10">
        <div class="text-xs uppercase tracking-[0.35em]">Official Ballot Terminal</div>
        <h1 class="text-4xl md:text-5xl font-bold">{{ cityName }}</h1>
        <p class="text-sm text-zinc-500">Please follow the on-screen instructions to cast your vote.</p>
      </header>

      <div v-if="step === 1" class="max-w-xl mx-auto bg-white border-2 border-black rounded-3xl p-8 shadow-[8px_8px_0_#000]">
        <h2 class="text-2xl font-semibold mb-4">Step 1: Enter Ballot ID</h2>
        <input
          v-model="ballotNumber"
          type="text"
          placeholder="BALLOT-ID"
          class="w-full border-2 border-black px-4 py-3 text-lg uppercase tracking-wider"
          :disabled="!isConfigured"
        />
        <button
          @click="startVoting"
          class="w-full mt-6 bg-black text-white text-lg py-3 disabled:opacity-50"
          :disabled="!isConfigured"
        >
          Continue
        </button>
        <p v-if="!isConfigured" class="mt-4 text-sm text-zinc-500">
          This machine is not configured yet. Please contact the admin.
        </p>
        <p v-if="errorMessage" class="mt-4 text-red-600 font-semibold">{{ errorMessage }}</p>
      </div>

      <div v-else-if="step === 2" class="space-y-6">
        <div class="bg-black text-white px-6 py-4 rounded-2xl flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="text-xs uppercase">Ballot ID</div>
            <div class="text-xl font-semibold tracking-wider">{{ ballotNumber }}</div>
          </div>
          <button @click="resetMachine" class="border border-white px-4 py-2 text-sm">Cancel</button>
        </div>

        <div class="space-y-6">
          <div
            v-for="position in electionStore.positions"
            :key="position.id"
            class="border-2 border-black rounded-2xl p-6"
          >
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-semibold">{{ position.title }}</h3>
              <span class="text-sm uppercase">Select {{ position.max_votes }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label
                v-for="candidate in candidatesByPosition.get(position.id) || []"
                :key="candidate.id"
                class="flex items-center gap-3 border-2 border-black px-4 py-3 text-lg cursor-pointer"
              >
                <input
                  type="checkbox"
                  class="h-5 w-5 accent-black"
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
          <button @click="resetMachine" class="border-2 border-black px-4 py-3 text-lg">Back</button>
          <button @click="submitVote" class="flex-1 bg-black text-white px-4 py-3 text-lg">Submit Vote</button>
        </div>
        <p v-if="errorMessage" class="text-red-600 font-semibold">{{ errorMessage }}</p>
      </div>

      <div v-else class="max-w-xl mx-auto bg-black text-white rounded-3xl p-10 text-center space-y-4">
        <div class="text-3xl font-bold">Vote Recorded</div>
        <p class="text-lg">Thank you for participating.</p>
        <button @click="resetMachine" class="mt-4 bg-white text-black px-6 py-3 text-lg">Next Voter</button>
      </div>
    </div>
  </div>
</template>
