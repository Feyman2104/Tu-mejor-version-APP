import { ref } from 'vue'

export type ToastType = 'info' | 'success' | 'warn' | 'error'

export interface Toast {
  id: number
  message: string
  type: ToastType
  duration: number
}

const toasts = ref<Toast[]>([])
let nextId = 0

// Deduplicación: no mostrar el mismo mensaje si ya está en la cola
function isDuplicate(message: string): boolean {
  return toasts.value.some(t => t.message === message)
}

function add(message: string, type: ToastType, duration = 4000): void {
  if (isDuplicate(message)) return
  const id = nextId++
  toasts.value.push({ id, message, type, duration })
  setTimeout(() => remove(id), duration)
}

function remove(id: number): void {
  const idx = toasts.value.findIndex(t => t.id === id)
  if (idx !== -1) toasts.value.splice(idx, 1)
}

export function useToasts() {
  return {
    toasts,
    info:    (msg: string, opts?: { duration?: number }) => add(msg, 'info',    opts?.duration),
    success: (msg: string, opts?: { duration?: number }) => add(msg, 'success', opts?.duration),
    warn:    (msg: string, opts?: { duration?: number }) => add(msg, 'warn',    opts?.duration),
    error:   (msg: string, opts?: { duration?: number }) => add(msg, 'error',   opts?.duration),
    remove,
  }
}
