<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const logout = () => {
  auth.logout()
  router.push('/login')
}

const navLinks = [
  { name: 'Dashboard', path: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Cities', path: '/cities', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
  { name: 'Positions', path: '/positions', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
  { name: 'Parties', path: '/parties', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
  { name: 'Candidates', path: '/candidates', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
  { name: 'Ballot Management', path: '/ballots', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { name: 'Results & Import', path: '/export', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
]
</script>

<template>
  <div class="flex h-screen bg-white">
    <!-- Sidebar -->
    <aside class="w-64 bg-zinc-950 text-zinc-400 flex flex-col border-r border-zinc-800">
      <div class="p-6 text-white font-bold text-lg tracking-tight flex items-center gap-2">
        <div class="w-6 h-6 bg-white text-black rounded flex items-center justify-center text-xs">C</div>
        Countera
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

      <!-- User Profile & Logout -->
      <div class="p-4 border-t border-zinc-900">
        <div class="flex items-center gap-3 px-2 mb-4">
          <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-400 text-xs font-bold">
            {{ auth.user?.username[0].toUpperCase() }}
          </div>
          <div class="flex flex-col">
            <span class="text-xs text-white font-medium">{{ auth.user?.username }}</span>
            <span class="text-[10px] text-zinc-500 uppercase tracking-tighter">System Admin</span>
          </div>
        </div>
        <button 
          @click="logout"
          class="w-full flex items-center px-3 py-2 text-xs text-zinc-500 hover:text-red-400 transition-colors"
        >
          Sign out
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-14 border-b border-zinc-100 flex items-center justify-between px-8 bg-white">
        <h2 class="text-sm font-medium text-zinc-500 uppercase tracking-widest">Management Console</h2>
        <div class="flex items-center gap-4 text-xs text-zinc-400">
          <span>{{ new Date().toLocaleDateString() }}</span>
          <span class="bg-zinc-100 text-zinc-600 px-2 py-1 rounded">Secure Node</span>
        </div>
      </header>

      <main class="flex-1 overflow-auto bg-zinc-50/50 p-10">
        <!-- Dashboard Content -->
        <router-view />
      </main>
    </div>
  </div>
</template>