<template>
  <div class="app-layout">
    <nav>
       <h1>ACM Admin</h1>
       <router-link to="/dashboard">Dashboard</router-link>
       <router-link to="/cities">Cities</router-link>
       <button @click="handleLogout">Logout</button>
    </nav>

    <main>
      <!-- This is where the specific page content (Views) will appear -->
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const handleLogout = () => {
  auth.logout()
  router.push('/login')
}
</script>