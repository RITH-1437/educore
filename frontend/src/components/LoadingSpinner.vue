<script setup>
defineProps({
  size: { type: String, default: 'md', validator: (value) => ['sm', 'md', 'lg'].includes(value) },
  label: { type: String, default: 'Loading' },
  inline: { type: Boolean, default: false },
})

const sizes = { sm: 'h-4 w-4 border-2', md: 'h-6 w-6 border-2', lg: 'h-8 w-8 border-[3px]' }
</script>

<template>
  <span :class="['items-center gap-3 text-small text-muted dark:text-dark-muted', inline ? 'inline-flex' : 'flex']" role="status">
    <span :class="['animate-spin rounded-pill border-primary/25 border-t-primary motion-reduce:animate-none dark:border-dark-primary/25 dark:border-t-dark-primary', sizes[size]]" aria-hidden="true" />
    <span>{{ label }}</span>
  </span>
</template>
