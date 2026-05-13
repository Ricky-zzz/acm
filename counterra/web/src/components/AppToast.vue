<script setup lang="ts">
import { computed } from 'vue'
import { useToastStore } from '../stores/toast'

const toastStore = useToastStore()

const toasts = computed(() => toastStore.toasts)

const toneClasses = (type: 'success' | 'error' | 'info') => {
  switch (type) {
    case 'success':
      return 'border-emerald-200 bg-white text-emerald-900'
    case 'error':
      return 'border-red-200 bg-white text-red-900'
    case 'info':
    default:
      return 'border-zinc-200 bg-white text-zinc-900'
  }
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 w-[92vw] max-w-sm space-y-2">
    <div
      v-for="t in toasts"
      :key="t.id"
      class="border rounded-lg shadow-sm px-4 py-3 flex items-start justify-between gap-3"
      :class="toneClasses(t.type)"
      role="status"
      aria-live="polite"
    >
      <p class="text-sm leading-snug">{{ t.message }}</p>
      <button
        class="text-xs px-2 py-1 rounded border border-current/20 hover:bg-black/5"
        type="button"
        @click="toastStore.dismiss(t.id)"
      >
        Dismiss
      </button>
    </div>
  </div>
</template>
