import { defineStore } from 'pinia'

export type ToastType = 'success' | 'error' | 'info'

export interface Toast {
  id: string
  type: ToastType
  message: string
}

interface PushToastOptions {
  type: ToastType
  message: string
  timeoutMs?: number
}

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [] as Toast[],
  }),

  actions: {
    push(options: PushToastOptions) {
      const id = `${Date.now()}_${Math.random().toString(16).slice(2)}`
      const toast: Toast = {
        id,
        type: options.type,
        message: options.message,
      }

      this.toasts.push(toast)

      const timeoutMs = options.timeoutMs ?? 5000
      if (timeoutMs > 0) {
        window.setTimeout(() => {
          this.dismiss(id)
        }, timeoutMs)
      }

      return id
    },

    success(message: string, timeoutMs?: number) {
      return this.push({ type: 'success', message, timeoutMs })
    },

    error(message: string, timeoutMs?: number) {
      return this.push({ type: 'error', message, timeoutMs })
    },

    info(message: string, timeoutMs?: number) {
      return this.push({ type: 'info', message, timeoutMs })
    },

    dismiss(id: string) {
      this.toasts = this.toasts.filter(t => t.id !== id)
    },

    clear() {
      this.toasts = []
    },
  },
})
