<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import type { City, Position, Party, Candidate } from '../../../types'

const props = defineProps<{
  isOpen: boolean
  isEditing: boolean
  cities: City[]
  positions: Position[]
  parties: Party[]
  initialData: Candidate | null
}>()

const emit = defineEmits(['close', 'save'])

const form = reactive({
  name: '',
  city_id: 0,
  position_id: 0,
  party_id: 0
})

const availablePositions = computed(() => {
  return props.positions.filter(p => p.city_id === form.city_id)
})

watch(
  () => props.isOpen,
  val => {
    if (val && props.initialData) {
      form.name = props.initialData.name
      form.city_id = props.initialData.city_id || 0
      form.position_id = props.initialData.position_id
      form.party_id = props.initialData.party_id
    } else if (val) {
      form.name = ''
      form.city_id = 0
      form.position_id = 0
      form.party_id = 0
    }
  }
)

watch(
  () => form.city_id,
  () => {
    if (!availablePositions.value.some(p => p.id === form.position_id)) {
      form.position_id = 0
    }
  }
)

const handleSave = () => {
  emit('save', { ...form })
}
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl border border-zinc-200">
      <div class="p-6 border-b border-zinc-100">
        <h3 class="text-lg font-semibold">{{ isEditing ? 'Edit' : 'Add' }} Candidate</h3>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Full Name</label>
          <input v-model="form.name" type="text" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none focus:border-zinc-900 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">City</label>
          <select v-model="form.city_id" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm">
            <option :value="0">Select City</option>
            <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <div v-if="form.city_id > 0">
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Position</label>
          <select v-model="form.position_id" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm">
            <option :value="0">Select Position</option>
            <option v-for="p in availablePositions" :key="p.id" :value="p.id">{{ p.title }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Political Party</label>
          <select v-model="form.party_id" class="w-full px-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg outline-none text-sm">
            <option :value="0">Select Party</option>
            <option v-for="pt in parties" :key="pt.id" :value="pt.id">{{ pt.name }} ({{ pt.alias }})</option>
          </select>
        </div>
      </div>

      <div class="p-6 bg-zinc-50 rounded-b-2xl flex gap-3">
        <button @click="emit('close')" class="flex-1 text-sm font-medium text-zinc-500">Cancel</button>
        <button @click="handleSave" class="flex-1 bg-zinc-900 text-white py-2 rounded-lg text-sm font-medium">Save Candidate</button>
      </div>
    </div>
  </div>
</template>
