<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  links: { type: Array, default: () => [] },
})

const currentPage = computed(() => props.links.find((link) => link.active)?.label ?? '1')
const navigationLinks = computed(() => props.links.filter((link) => link.url || ['Previous', 'Next'].includes(link.label)))
</script>

<template>
  <nav
    v-if="links.length > 3"
    class="flex flex-wrap items-center justify-between gap-3 border-t border-border-default px-4 py-3 dark:border-dark-border sm:px-6"
    aria-label="Pagination"
  >
    <p class="text-small text-muted dark:text-dark-muted">Page {{ currentPage }}</p>
    <div class="flex flex-wrap items-center gap-1">
      <template v-for="link in navigationLinks" :key="`${link.label}-${link.url || 'disabled'}`">
        <Link
          v-if="link.url"
          :href="link.url"
          preserve-scroll
          class="inline-flex min-h-9 items-center justify-center rounded-md border px-3 text-small font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
          :class="link.active
            ? 'border-primary bg-primary text-white'
            : 'border-border-default bg-surface text-ink hover:bg-background dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2'"
          :aria-current="link.active ? 'page' : undefined"
        >
          {{ link.label }}
        </Link>
        <span v-else class="inline-flex min-h-9 items-center justify-center rounded-md border border-border-default px-3 text-small text-muted opacity-50 dark:border-dark-border dark:text-dark-muted" aria-disabled="true">
          {{ link.label }}
        </span>
      </template>
    </div>
  </nav>
</template>
