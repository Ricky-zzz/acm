<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { usePartyStore } from '../../stores/party'
import type { Party } from '../../types'

const partyStore = usePartyStore()

const showAddForm = ref(false)
const formData = ref({
  name: '',
  alias: ''
})
const editingId = ref<number | null>(null)

const serverErrors = ref<Record<string, string>>({})

onMounted(async () => {
  await partyStore.fetchParties()
})

const resetForm = () => {
  formData.value = { name: '', alias: '' }
  editingId.value = null
  serverErrors.value = {}
  showAddForm.value = false
}

const submitForm = async () => {
  serverErrors.value = {}

  try {
    if (editingId.value) {
      await partyStore.updateParty(editingId.value, formData.value.name, formData.value.alias)
    } else {
      await partyStore.addParty(formData.value.name, formData.value.alias)
    }
    resetForm()
  } catch (error: any) {
    if (error.response?.status === 422) {
      serverErrors.value = error.response.data.errors || {}
    }
  }
}

const deleteParty = async (id: number) => {
  if (confirm('Are you sure you want to delete this party?')) {
    await partyStore.deleteParty(id)
  }
}

const editParty = (party: Party) => {
  editingId.value = party.id ?? null
  formData.value = {
    name: party.name,
    alias: party.alias
  }
  showAddForm.value = true
  serverErrors.value = {}
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-zinc-900">Parties</h1>
      <button
        @click="showAddForm = !showAddForm"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        {{ showAddForm ? 'Cancel' : 'Add Party' }}
      </button>
    </div>

    <!-- Add/Edit Form -->
    <div v-if="showAddForm" class="bg-white p-6 rounded-lg border border-zinc-200 shadow-sm">
      <h2 class="text-lg font-semibold mb-4">{{ editingId ? 'Edit Party' : 'New Party' }}</h2>
      <form @submit.prevent="submitForm" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-zinc-700 mb-1">Name</label>
          <input
            v-model="formData.name"
            type="text"
            placeholder="Enter party name"
            class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <span v-if="serverErrors.name" class="text-red-500 text-xs">{{ serverErrors.name }}</span>
        </div>

        <div>
          <label class="block text-sm font-medium text-zinc-700 mb-1">Alias</label>
          <input
            v-model="formData.alias"
            type="text"
            placeholder="Enter party alias"
            maxlength="20"
            class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <span v-if="serverErrors.alias" class="text-red-500 text-xs">{{ serverErrors.alias }}</span>
        </div>

        <div class="flex gap-2">
          <button
            type="submit"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
          >
            {{ editingId ? 'Update' : 'Create' }}
          </button>
          <button
            type="button"
            @click="resetForm"
            class="px-4 py-2 bg-gray-300 text-zinc-700 rounded-lg hover:bg-gray-400 transition"
          >
            Reset
          </button>
        </div>
      </form>
    </div>

    <!-- Parties Table -->
    <div class="bg-white rounded-lg border border-zinc-200 shadow-sm overflow-hidden">
      <table class="w-full">
        <thead class="bg-zinc-50 border-b border-zinc-200">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900">Name</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900">Alias</th>
            <th class="px-6 py-3 text-right text-sm font-semibold text-zinc-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200">
          <tr v-for="party in partyStore.parties" :key="party.id" class="hover:bg-zinc-50">
            <td class="px-6 py-4 text-sm text-zinc-900">{{ party.name }}</td>
            <td class="px-6 py-4 text-sm text-zinc-600">{{ party.alias }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                @click="editParty(party)"
                class="text-blue-600 hover:text-blue-800 font-medium"
              >
                Edit
              </button>
              <button
                @click="deleteParty(party.id!)"
                class="text-red-600 hover:text-red-800 font-medium"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="partyStore.parties.length === 0" class="px-6 py-8 text-center text-zinc-500">
        No parties yet. Create one to get started!
      </div>
    </div>
  </div>
</template>
