<script setup>
defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
})
</script>

<template>
  <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-caption font-semibold uppercase tracking-widest text-primary dark:text-dark-primary">{{ eyebrow }}</p>
      <h1 class="mt-2 text-h3 font-display font-semibold text-ink dark:text-dark-ink sm:text-h1">{{ title }}</h1>
      <p v-if="description || $slots.description" class="mt-2 max-w-2xl text-small text-muted dark:text-dark-muted sm:text-body">
        <slot name="description">{{ description }}</slot>
      </p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-3"><slot name="actions" /></div>
  </header>
</template>
