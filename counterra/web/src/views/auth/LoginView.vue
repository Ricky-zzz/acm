<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ username: '', password: '' })
const isLoading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''

  const success = await auth.login(form.username, form.password)

  if (success) {
    router.push('/dashboard')
  } else {
    errorMessage.value = 'Invalid credentials. Access denied.'
  }
  isLoading.value = false
}
</script>

<template>
  <div class="min-h-screen bg-zinc-50 flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-100">
      <!-- Logo/Brand Section -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-black text-white rounded-xl mb-4 font-bold text-xl">
          A
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">ACM Countera</h1>
        <p class="text-sm text-zinc-500 mt-2">Sign in to the central counting terminal</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white border border-zinc-200 p-8 rounded-2xl shadow-sm">
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div v-if="errorMessage" class="p-3 text-xs font-medium bg-red-50 text-red-600 border border-red-100 rounded-lg">
            {{ errorMessage }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Username</label>
            <input 
              v-model="form.username"
              type="text" 
              required
              class="w-full px-4 py-2.5 bg-white border border-zinc-200 rounded-lg focus:ring-1 focus:ring-black focus:border-black outline-none transition-all text-sm"
              placeholder="admin"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Password</label>
            <input 
              v-model="form.password"
              type="password" 
              required
              class="w-full px-4 py-2.5 bg-white border border-zinc-200 rounded-lg focus:ring-1 focus:ring-black focus:border-black outline-none transition-all text-sm"
              placeholder="••••••••"
            />
          </div>

          <button 
            type="submit"
            :disabled="isLoading"
            class="w-full bg-zinc-900 hover:bg-black text-white text-sm font-medium py-2.5 rounded-lg transition-all disabled:opacity-50 mt-2"
          >
            {{ isLoading ? 'Verifying...' : 'Sign in' }}
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-zinc-400 mt-8 font-mono">
        SECURE ENCRYPTED TERMINAL v1.0
      </p>
    </div>
  </div>
</template>