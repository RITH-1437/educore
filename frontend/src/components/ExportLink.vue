<script setup>
import { Download } from '@lucide/vue'

// A plain <a> (not an Inertia Link) so the browser downloads the CSV file.
// Styled like a small secondary BaseButton.
defineProps({
  href: { type: String, required: true },
  label: { type: String, default: 'Export CSV' },
  ariaLabel: { type: String, default: '' },
})
</script>

<template>
  <a
    :href="href"
    :aria-label="ariaLabel || undefined"
    class="inline-flex min-h-8 items-center justify-center gap-2 rounded-md border border-border-default bg-surface px-3 py-1.5 text-caption font-medium text-ink transition duration-150 ease-out hover:bg-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2"
  >
    <Download class="size-4" aria-hidden="true" />
    {{ label }}
  </a>
</template>
