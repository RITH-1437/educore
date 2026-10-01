<script setup>
import { TriangleAlert } from '@lucide/vue'
import BaseButton from './BaseButton.vue'

defineProps({
  title: { type: String, default: 'Unable to load data' },
  description: { type: String, default: 'Please try again.' },
  retryLabel: { type: String, default: 'Try again' },
  /** Set false to hide the retry button when there is nothing to retry. */
  retryable: { type: Boolean, default: true },
})

defineEmits(['retry'])
</script>

<template>
  <section class="mx-auto flex max-w-md flex-col items-center gap-3 py-8 text-center" role="alert">
    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-error/10 text-error dark:bg-error/15 dark:text-red-300">
      <TriangleAlert class="h-6 w-6" aria-hidden="true" />
    </span>
    <h3 class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ title }}</h3>
    <p class="text-small text-muted dark:text-dark-muted">{{ description }}</p>
    <BaseButton v-if="retryable" size="sm" variant="secondary" @click="$emit('retry')">{{ retryLabel }}</BaseButton>
  </section>
</template>
