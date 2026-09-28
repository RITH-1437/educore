<script setup>
const props = defineProps({
  variant: { type: String, default: 'primary', validator: (value) => ['primary', 'secondary', 'success', 'warning', 'error', 'muted'].includes(value) },
  dot: { type: Boolean, default: false },
  size: { type: String, default: 'sm', validator: (value) => ['sm', 'md'].includes(value) },
  removable: { type: Boolean, default: false },
  removableAriaLabel: { type: String, default: 'Remove badge' },
})

const emit = defineEmits(['remove'])

const variantClasses = {
  primary: 'bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary',
  secondary: 'bg-secondary/15 text-primary-dark dark:bg-secondary/15 dark:text-dark-ink',
  success: 'bg-success/10 text-success dark:bg-success/15 dark:text-green-300',
  warning: 'bg-warning/15 text-primary-dark dark:bg-warning/15 dark:text-amber-200',
  error: 'bg-error/10 text-error dark:bg-error/15 dark:text-red-300',
  muted: 'bg-muted/10 text-muted dark:bg-dark-surface-2 dark:text-dark-muted',
}

const dotClasses = {
  primary: 'bg-primary dark:bg-dark-primary',
  secondary: 'bg-secondary',
  success: 'bg-success dark:bg-green-300',
  warning: 'bg-warning dark:bg-amber-300',
  error: 'bg-error dark:bg-red-300',
  muted: 'bg-muted dark:bg-dark-muted',
}
</script>

<template>
  <span :class="['inline-flex w-fit items-center gap-1.5 rounded-pill font-medium', size === 'sm' ? 'px-2 py-1 text-caption' : 'px-3 py-1.5 text-small', variantClasses[variant]]">
    <span v-if="dot" :class="['h-1.5 w-1.5 shrink-0 rounded-full', dotClasses[variant]]" aria-hidden="true" />
    <slot />
    <button
      v-if="removable"
      type="button"
      class="ml-0.5 rounded-pill p-0.5 hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary"
      :aria-label="removableAriaLabel"
      @click="emit('remove')"
    >
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
      </svg>
    </button>
  </span>
</template>
