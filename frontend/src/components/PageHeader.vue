<script setup>
defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
})
</script>

<template>
  <header class="flex flex-col justify-between gap-4 motion-safe:animate-section-in sm:flex-row sm:items-end">
    <div class="min-w-0">
      <h1 class="font-display text-h3 font-semibold tracking-tight text-ink dark:text-dark-ink sm:text-h1">{{ title }}</h1>
      <p v-if="description || $slots.description" class="mt-2 max-w-2xl text-small text-muted dark:text-dark-muted sm:text-body">
        <slot name="description">{{ description }}</slot>
      </p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-3"><slot name="actions" /></div>
  </header>
</template>
