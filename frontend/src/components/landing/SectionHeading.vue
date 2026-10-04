<script setup>
import Reveal from './Reveal.vue'

defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  align: { type: String, default: 'center' },
  dark: { type: Boolean, default: false },
  id: { type: String, default: undefined },
})
</script>

<template>
  <Reveal class="max-w-3xl" :class="align === 'left' ? 'text-left' : 'mx-auto text-center'">
    <p
      v-if="eyebrow"
      class="text-caption font-semibold tracking-widest uppercase"
      :class="dark ? 'text-secondary' : 'text-primary dark:text-dark-primary'"
    >
      {{ eyebrow }}
    </p>
    <h2
      :id="id"
      class="font-display mt-3 text-h2 text-balance"
      :class="dark ? 'text-dark-ink' : 'text-primary-dark dark:text-dark-ink'"
    >
      {{ title }}
    </h2>
    <p
      v-if="description"
      class="mt-4 text-body"
      :class="dark ? 'text-dark-muted' : 'text-muted dark:text-dark-muted'"
    >
      {{ description }}
    </p>
  </Reveal>
</template>
