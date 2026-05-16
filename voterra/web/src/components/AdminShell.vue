<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const navLinks = computed(() => [
  { name: 'Admin Config', path: '/admin-config' },
  { name: 'Transmission', path: '/admin-transmission' },
  { name: 'Local Tally', path: '/admin-tally' },
])

const goToVoter = () => {
  router.push('/')
}

const logout = () => {
  authStore.logout()
  router.push('/admin-login')
}
</script>

<template>
  <div class="flex h-screen bg-white text-zinc-900">
    <aside class="w-64 bg-zinc-950 text-zinc-400 flex flex-col border-r border-zinc-800">
      <div class="p-6 text-white font-bold text-lg tracking-tight flex items-center gap-2">
        <div class="w-6 h-6 bg-white text-black rounded flex items-center justify-center text-xs">V</div>
        Voterra
      </div>

      <nav class="flex-1 px-4 py-4 space-y-1">
        <router-link
          v-for="link in navLinks"
          :key="link.path"
          :to="link.path"
          class="flex items-center px-3 py-2 text-sm rounded-md transition-colors hover:text-white hover:bg-zinc-900"
          active-class="bg-zinc-900 text-white"
        >
          {{ link.name }}
        </router-link>
      </nav>

      <div class="p-4 border-t border-zinc-900 space-y-2">
        <button
          @click="goToVoter"
          class="w-full flex items-center px-3 py-2 text-xs text-zinc-400 hover:text-white hover:bg-zinc-900 rounded-md transition-colors"
        >
          Go to Voter Screen
        </button>
        <button
          @click="logout"
          class="w-full flex items-center px-3 py-2 text-xs text-zinc-500 hover:text-red-400 transition-colors"
        >
          Sign out
        </button>
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-14 border-b border-zinc-100 flex items-center justify-between px-8 bg-white">
        <h2 class="text-sm font-medium text-zinc-500 uppercase tracking-widest">Management Console</h2>
        <div class="flex items-center gap-4 text-xs text-zinc-400">
          <span>{{ new Date().toLocaleDateString() }}</span>
          <span class="bg-zinc-100 text-zinc-600 px-2 py-1 rounded">Secure Node</span>
        </div>
      </header>

      <main class="flex-1 overflow-auto bg-zinc-50/50 p-10">
        <slot />
      </main>
    </div>
  </div>
</template>
