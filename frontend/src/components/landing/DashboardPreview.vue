<script setup>
import { Bell, Search } from '@lucide/vue'
import { computed } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import { previews } from './showcaseData'

// A scaled-down, non-interactive mirror of the real dashboard shell (sidebar,
// topbar, page header, stat cards, list cards). The admin view shows the live
// `stats`; the personal views (lecturer, student) show labelled demo data.
// Decorative: callers wrap it in a <figure> with a caption.
const props = defineProps({
  role: { type: String, default: 'admin' },
  stats: { type: Object, default: null },
})

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'
const view = computed(() => previews[props.role])
const resolve = (value) => (typeof value === 'function' ? value(props.stats) : value)
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-border-default bg-surface text-left shadow-lg dark:border-dark-border dark:bg-dark-surface" aria-hidden="true">
    <div class="flex items-center gap-2 border-b border-border-default bg-background px-4 py-3 dark:border-dark-border dark:bg-dark-bg">
      <span class="h-3 w-3 rounded-pill bg-border-muted dark:bg-dark-border" />
      <span class="h-3 w-3 rounded-pill bg-border-muted dark:bg-dark-border" />
      <span class="h-3 w-3 rounded-pill bg-border-muted dark:bg-dark-border" />
      <span class="ml-2 flex min-w-0 items-center gap-2 text-caption font-semibold text-ink dark:text-dark-ink">
        <img :src="symbolMark" alt="" class="h-4 w-4 rounded-sm" />
        <span class="truncate">EduCore · {{ view.title }}</span>
      </span>
      <BaseBadge v-if="view.live" variant="success" dot class="ml-auto shrink-0">Live data</BaseBadge>
      <BaseBadge v-else variant="warning" class="ml-auto shrink-0">Demo data</BaseBadge>
    </div>

    <div class="flex">
      <div class="hidden w-12 shrink-0 border-r border-border-default bg-background p-2 sm:block dark:border-dark-border dark:bg-dark-bg">
        <ul class="space-y-1">
          <li
            v-for="(item, i) in view.nav"
            :key="item.label"
            class="flex items-center gap-2 rounded-md p-2 text-caption"
            :class="i === 0 ? 'bg-primary/10 font-semibold text-primary dark:bg-dark-primary/15 dark:text-dark-primary' : 'text-muted dark:text-dark-muted'"
          >
            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                      </li>
        </ul>
      </div>

      <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between border-b border-border-default px-4 py-2 dark:border-dark-border">
          <p class="text-caption text-muted dark:text-dark-muted">Dashboard</p>
          <div class="flex items-center gap-3 text-muted dark:text-dark-muted">
            <Search class="h-4 w-4" />
            <Bell class="h-4 w-4" />
            <span class="flex h-6 w-6 items-center justify-center rounded-pill bg-primary text-caption font-semibold text-white dark:bg-dark-primary dark:text-dark-bg">
              {{ view.eyebrow[0] }}
            </span>
          </div>
        </div>

        <div class="space-y-4 p-4 sm:p-5">
          <div>
            <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">{{ view.eyebrow }}</p>
            <p class="mt-1 text-h4 text-primary-dark dark:text-dark-ink">{{ view.heading }}</p>
            <p class="truncate text-caption text-muted dark:text-dark-muted">{{ resolve(view.description) }}</p>
          </div>

          <div v-for="group in view.groups" :key="group.heading" class="space-y-2">
            <p v-if="group.heading" class="text-small font-semibold text-ink dark:text-dark-ink">{{ group.heading }}</p>
            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
              <div v-for="stat in group.stats" :key="stat.label" class="min-w-0 rounded-lg border border-border-default bg-surface p-3 dark:border-dark-border dark:bg-dark-surface">
                <p class="truncate text-caption text-muted dark:text-dark-muted">{{ stat.label }}</p>
                <p class="mt-1 text-h4 font-bold tabular-nums text-ink dark:text-dark-ink">{{ resolve(stat.value) }}</p>
                <p class="truncate text-caption text-muted dark:text-dark-muted">{{ stat.detail }}</p>
              </div>
            </div>
          </div>

          <div v-if="view.panels.length" class="grid gap-3 md:grid-cols-2">
            <div v-for="panel in view.panels" :key="panel.title" class="rounded-lg border border-border-default p-3 dark:border-dark-border">
              <p class="text-small font-semibold text-ink dark:text-dark-ink">{{ panel.title }}</p>
              <ul class="mt-1 divide-y divide-border-default dark:divide-dark-border">
                <li v-for="row in panel.rows" :key="row.title" class="flex items-center justify-between gap-3 py-2">
                  <div class="min-w-0">
                    <p class="truncate text-small font-medium text-ink dark:text-dark-ink">{{ row.title }}</p>
                    <p class="truncate text-caption text-muted dark:text-dark-muted">{{ row.meta }}</p>
                  </div>
                  <BaseBadge :variant="row.variant" class="shrink-0">{{ row.badge }}</BaseBadge>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
