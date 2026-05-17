<script setup lang="ts">
import { onMounted } from 'vue'
import { useCityStore } from '../../stores/city'
import { useCandidateStore } from '../../stores/candidate'

const cityStore = useCityStore()
const candidateStore = useCandidateStore()

onMounted(() => {
  cityStore.fetchCities()
  candidateStore.fetchCandidates()
})
</script>

<template>
  <div>
    <h1 class="text-3xl font-bold mb-4">Welcome to Countera</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-gray-500 text-sm font-medium uppercase">Registered Cities</h3>
        <p class="text-2xl font-bold">{{ cityStore.cities.length }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-gray-500 text-sm font-medium uppercase">Total Candidates</h3>
        <p class="text-2xl font-bold">{{ candidateStore.candidates.length }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-gray-500 text-sm font-medium uppercase">System Status</h3>
        <p class="text-green-500 font-semibold">Active</p>
      </div>
    </div>
  </div>
</template>