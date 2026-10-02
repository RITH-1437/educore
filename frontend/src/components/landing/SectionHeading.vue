<script setup>
import Reveal from './Reveal.vue'

defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  align: { type: String, default: 'center' },
  dark: { type: Boolean, default: false },
})
</script>

<template>
  <Reveal class="max-w-3xl" :class="align === 'left' ? 'text-left' : 'mx-auto text-center'">
    <div
      v-if="eyebrow"
      class="flex items-center gap-2"
      :class="align === 'left' ? 'justify-start' : 'justify-center'"
    >
      <span
        class="inline-block h-px w-5"
        :class="dark ? 'bg-sky-400/50' : 'bg-blue-600/40'"
        aria-hidden="true"
      />
      <p
        class="text-xs font-semibold tracking-[0.2em] uppercase"
        :class="dark ? 'text-sky-400' : 'text-blue-600'"
      >
        {{ eyebrow }}
      </p>
      <span
        v-if="align !== 'left'"
        class="inline-block h-px w-5"
        :class="dark ? 'bg-sky-400/50' : 'bg-blue-600/40'"
        aria-hidden="true"
      />
    </div>
    <h2
      class="font-display mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
      :class="dark ? 'text-white' : 'text-slate-950'"
    >
      {{ title }}
    </h2>
    <p
      v-if="description"
      class="mt-4 text-base leading-relaxed sm:text-lg"
      :class="dark ? 'text-slate-400' : 'text-slate-600'"
    >
      {{ description }}
    </p>
  </Reveal>
</template>