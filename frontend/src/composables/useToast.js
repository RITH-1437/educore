import { reactive } from 'vue'

// One app-wide queue of small toasts, shown bottom-right by ToastRegion
// (UI-COMPONENTS §12). Server flash messages arrive through DefaultLayout;
// any component can also call toast.success() / toast.error().
const MAX_VISIBLE = 3
const DURATION = { success: 4000, info: 4000, error: 6000 }
const RESUME_AFTER = 2000
const DUPLICATE_WINDOW = 1000

const toasts = reactive([])
const timers = new Map()
let nextId = 0

const dismiss = (id) => {
  clearTimeout(timers.get(id))
  timers.delete(id)
  const index = toasts.findIndex((item) => item.id === id)
  if (index !== -1) toasts.splice(index, 1)
}

const schedule = (id, ms) => {
  clearTimeout(timers.get(id))
  timers.set(id, setTimeout(() => dismiss(id), ms))
}

const push = (type, message) => {
  if (!message) return
  // The same message twice within a second is one event (e.g. a double submit).
  const now = Date.now()
  if (toasts.some((item) => item.type === type && item.message === message && now - item.at < DUPLICATE_WINDOW)) return

  const id = ++nextId
  toasts.push({ id, type, message, at: now })
  while (toasts.length > MAX_VISIBLE) dismiss(toasts[0].id)
  schedule(id, DURATION[type])
}

// Hovering or focusing a toast keeps it open; leaving gives it a short while longer.
const pause = (id) => clearTimeout(timers.get(id))
const resume = (id) => schedule(id, RESUME_AFTER)

export const toast = {
  success: (message) => push('success', message),
  error: (message) => push('error', message),
  info: (message) => push('info', message),
}

export function useToast() {
  return { toasts, dismiss, pause, resume, ...toast }
}
