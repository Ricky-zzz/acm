<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const navLinks = computed(() => [
  { name: 'Admin Config', path: '/admin-config' },
  { name: 'Tally & Transmission', path: '/admin-results' },
])

const goToVoter = () => {
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-[#f6f4f0] text-zinc-900">
    <div class="mx-auto max-w-6xl px-6 py-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <div class="text-xs uppercase tracking-[0.25em] text-zinc-500">Voterra Admin</div>
          <h1 class="text-3xl font-semibold">Machine Control Center</h1>
        </div>
        <button
          @click="goToVoter"
          class="border border-zinc-300 px-4 py-2 text-sm rounded-full hover:border-zinc-500"
        >
          Go to Voter Screen
        </button>
      </header>

      <div class="flex flex-col lg:flex-row gap-6">
        <aside class="w-full lg:w-56 bg-white border border-zinc-200 rounded-2xl p-4 shadow-sm">
          <div class="text-xs uppercase text-zinc-400 mb-3">Admin Menu</div>
          <nav class="space-y-2">
            <button
              v-for="link in navLinks"
              :key="link.path"
              class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium border border-transparent hover:border-zinc-200 hover:bg-zinc-50"
              :class="$route.path === link.path ? 'bg-zinc-900 text-white' : 'text-zinc-700'"
              @click="router.push(link.path)"
            >
              {{ link.name }}
            </button>
          </nav>
        </aside>

        <main class="flex-1">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
