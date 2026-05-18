<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useSetupStore } from '../../stores/setup'

const router = useRouter()
const authStore = useAuthStore()
const setupStore = useSetupStore()

const username = ref('')
const password = ref('')
const error = ref('')
const step = ref<'admin' | 'passkey1' | 'passkey2'>('admin')
const officerUsername = ref('')
const officerPasskey = ref('')
const officerError = ref('')

const isConfigured = computed(() => Boolean(setupStore.status?.city_id))

const officerCreds = {
  passkey1: { username: 'authofficer1', passkey: 'PX1-8F3D-9K2L-7M1Q' },
  passkey2: { username: 'authofficer2', passkey: 'PX2-4R6T-1V8B-0N5C' }
}

onMounted(async () => {
  await setupStore.fetchStatus()
})

const submit = async () => {
  error.value = ''
  const success = authStore.login(username.value.trim(), password.value)
  if (success) {
    await setupStore.fetchStatus()
    if (isConfigured.value) {
      router.push('/admin-config')
      return
    }
    step.value = 'passkey1'
    officerUsername.value = ''
    officerPasskey.value = ''
  } else {
    error.value = authStore.lastError || 'Login failed'
  }
}

const submitOfficer = () => {
  officerError.value = ''
  const inputUser = officerUsername.value.trim()
  const inputKey = officerPasskey.value.trim()
  const target = step.value === 'passkey1' ? officerCreds.passkey1 : officerCreds.passkey2

  if (inputUser !== target.username || inputKey !== target.passkey) {
    authStore.logout()
    step.value = 'admin'
    officerUsername.value = ''
    officerPasskey.value = ''
    error.value = 'Special passkey failed. Please sign in again.'
    return
  }

  if (step.value === 'passkey1') {
    step.value = 'passkey2'
    officerUsername.value = ''
    officerPasskey.value = ''
    return
  }

  router.push('/admin-config')
}
</script>

<template>
  <div class="min-h-screen bg-zinc-50/50 flex items-center justify-center px-6">
    <div class="w-full max-w-md bg-white border border-zinc-200 rounded-2xl p-8 shadow-sm">
      <div class="text-xs uppercase tracking-[0.3em] text-zinc-400">Management Console</div>
      <h1 class="text-2xl font-semibold mt-2">Voterra Admin</h1>

      <div v-if="step === 'admin'">
        <p class="text-sm text-zinc-500 mt-2">Use the local admin credentials to access machine controls.</p>

        <div class="mt-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Username</label>
            <input
              v-model="username"
              type="text"
              placeholder="Username"
              class="w-full px-4 py-2 border border-zinc-200 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Password</label>
            <input
              v-model="password"
              type="password"
              placeholder="**********"
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

      <div v-else>
        <p class="text-sm text-zinc-500 mt-2">
          {{ step === 'passkey1' ? 'Special Passkey 1' : 'Special Passkey 2' }} required.
        </p>

        <div class="mt-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Officer Username</label>
            <input
              v-model="officerUsername"
              type="text"
              placeholder="authofficer"
              class="w-full px-4 py-2 border border-zinc-200 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-zinc-400 uppercase mb-2">Passkey</label>
            <input
              v-model="officerPasskey"
              type="password"
              placeholder="****************"
              class="w-full px-4 py-2 border border-zinc-200 rounded-lg text-sm"
            />
          </div>
        </div>

        <button
          @click="submitOfficer"
          class="w-full mt-6 bg-zinc-900 text-white py-2 rounded-lg text-sm font-medium"
        >
          Verify Passkey
        </button>

        <p v-if="officerError" class="mt-4 text-sm text-red-600">{{ officerError }}</p>
      </div>
    </div>
  </div>
</template>
