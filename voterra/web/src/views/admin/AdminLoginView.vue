<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const error = ref('')

const submit = () => {
  error.value = ''
  const success = authStore.login(username.value.trim(), password.value)
  if (success) {
    router.push('/admin-config')
  } else {
    error.value = authStore.lastError || 'Login failed'
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#f6f4f0] flex items-center justify-center px-6">
    <div class="w-full max-w-md bg-white border border-zinc-200 rounded-2xl p-8 shadow-sm">
      <div class="text-xs uppercase tracking-[0.3em] text-zinc-400">Voterra Admin</div>
      <h1 class="text-2xl font-semibold mt-2">Sign in</h1>
      <p class="text-sm text-zinc-500 mt-2">Use the local admin credentials to access machine controls.</p>

      <div class="mt-6 space-y-4">
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Username</label>
          <input
            v-model="username"
            type="text"
            placeholder="admin"
            class="w-full px-4 py-2 border border-zinc-200 rounded-lg text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Password</label>
          <input
            v-model="password"
            type="password"
            placeholder="admin123"
            class="w-full px-4 py-2 border border-zinc-200 rounded-lg text-sm"
          />
        </div>
      </div>

      <button
        @click="submit"
        class="w-full mt-6 bg-zinc-900 text-white py-2 rounded-lg text-sm font-medium"
      >
        Sign in
      </button>

      <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
    </div>
  </div>
</template>
