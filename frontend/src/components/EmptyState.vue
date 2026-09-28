<script setup>
import { Link } from '@inertiajs/vue3'
import { Inbox } from '@lucide/vue'
import BaseButton from './BaseButton.vue'

defineProps({
  title: { type: String, default: 'Nothing here yet' },
  description: { type: String, default: '' },
  actionLabel: { type: String, default: '' },
  actionHref: { type: String, default: '' },
})
</script>

<template>
  <section class="mx-auto flex max-w-md flex-col items-center gap-3 py-8 text-center" aria-live="polite">
    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
      <slot name="icon"><Inbox class="h-6 w-6" aria-hidden="true" /></slot>
    </span>
    <h3 class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ title }}</h3>
    <p v-if="description" class="text-small text-muted dark:text-dark-muted">{{ description }}</p>
    <slot name="action">
      <Link v-if="actionHref && actionLabel" :href="actionHref">
        <BaseButton size="sm">{{ actionLabel }}</BaseButton>
      </Link>
    </slot>
  </section>
</template>
