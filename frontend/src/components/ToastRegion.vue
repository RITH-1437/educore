<script setup>
import { CircleAlert, CircleCheck, Info, X } from '@lucide/vue'
import { useToast } from '../composables/useToast'

// Small, short-lived feedback at the bottom right (UI-COMPONENTS §12).
// Success / info are polite status messages; errors are announced as alerts.
const { toasts, dismiss, pause, resume } = useToast()

const icons = { success: CircleCheck, error: CircleAlert, info: Info }
const tones = {
  success: 'text-success dark:text-green-300',
  error: 'text-error dark:text-red-300',
  info: 'text-primary dark:text-dark-primary',
}
</script>

<template>
  <div
    class="pointer-events-none fixed inset-x-4 bottom-4 z-[80] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6"
    role="region"
    aria-label="Notifications"
    aria-live="polite"
  >
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-out"
      leave-to-class="opacity-0"
      move-class="transition duration-200 ease-out"
    >
      <div
        v-for="item in toasts"
        :key="item.id"
        :role="item.type === 'error' ? 'alert' : 'status'"
        class="pointer-events-auto flex w-full items-start gap-3 rounded-lg border border-border-default bg-surface px-4 py-3 shadow-lg sm:w-80 dark:border-dark-border dark:bg-dark-surface"
        @mouseenter="pause(item.id)"
        @mouseleave="resume(item.id)"
        @focusin="pause(item.id)"
        @focusout="resume(item.id)"
      >
        <component :is="icons[item.type]" class="mt-0.5 h-4 w-4 shrink-0" :class="tones[item.type]" aria-hidden="true" />
        <p class="min-w-0 flex-1 text-small text-ink dark:text-dark-ink">{{ item.message }}</p>
        <button
          type="button"
          class="-m-1 shrink-0 rounded-md p-1 text-muted transition-colors duration-150 hover:text-ink focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:text-dark-ink"
          aria-label="Dismiss notification"
          @click="dismiss(item.id)"
        >
          <X class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
