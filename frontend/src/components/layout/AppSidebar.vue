<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronsLeft, ChevronsRight, X } from '@lucide/vue'
import { useNavigation } from '../../composables/useNavigation'

defineProps({
  collapsed: { type: Boolean, default: false },
  mobileOpen: { type: Boolean, default: false },
  /** True when the drawer is off-screen and must not be reachable by keyboard. */
  hidden: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle-collapse', 'close'])
const { navGroups, homeHref, isActive } = useNavigation()
const closeButton = ref(null)
const appIcon = '/assets/logo/educore-app-icon.png'

defineExpose({ focusClose: () => closeButton.value?.focus() })

const itemClass = (active, collapsed) => [
  'group relative flex min-h-11 items-center gap-3 rounded-lg px-3 text-small font-medium transition-colors duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none dark:focus-visible:outline-dark-primary',
  active
    ? 'bg-primary/5 font-semibold text-primary dark:bg-dark-primary/10 dark:text-dark-primary'
    : 'text-muted hover:bg-background hover:text-ink dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink',
  collapsed ? 'lg:justify-center lg:px-0' : '',
]

// Shown on hover/focus only while the sidebar is collapsed on desktop.
const tooltipClass = 'pointer-events-none absolute left-full top-1/2 z-50 ml-3 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-primary-dark px-3 py-2 text-caption font-medium text-white opacity-0 shadow-md transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100 lg:block motion-reduce:transition-none'
</script>

<template>
  <aside
    id="app-sidebar"
    :inert="hidden"
    class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col glass-surface border-r border-border-default transition-[width,transform] duration-300 ease-out dark:border-dark-border lg:translate-x-0 motion-reduce:transition-none"
    :class="[collapsed ? 'lg:w-20' : 'lg:w-72', mobileOpen ? 'translate-x-0 shadow-lg' : '-translate-x-full lg:translate-x-0']"
    aria-label="Main sidebar"
  >
    <div class="flex h-16 shrink-0 items-center justify-between px-4">
      <Link :href="homeHref" class="flex min-w-0 items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" aria-label="EduCore home">
        <img :src="appIcon" alt="" class="h-9 w-9 shrink-0 rounded-lg" width="36" height="36" />
        <span v-if="!collapsed" class="min-w-0">
          <span class="block font-display text-body font-semibold leading-5 text-ink dark:text-dark-ink">EduCore</span>
          <span class="block truncate text-caption text-muted dark:text-dark-muted">Academic administration</span>
        </span>
      </Link>
      <button ref="closeButton" type="button" class="rounded-md p-2 text-muted hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:hidden" aria-label="Close navigation" @click="emit('close')">
        <X class="h-5 w-5" aria-hidden="true" />
      </button>
    </div>

    <nav class="min-h-0 flex-1 space-y-6 px-3 py-4" :class="collapsed ? 'lg:overflow-visible overflow-y-auto' : 'overflow-y-auto'" aria-label="Primary navigation">
      <section v-for="group in navGroups" :key="group.label">
        <h2 v-if="!collapsed" class="mb-2 px-3 text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ group.label }}</h2>
        <div v-else class="mx-3 mb-2 hidden border-t border-border-default dark:border-dark-border lg:block" aria-hidden="true" />
        <ul class="space-y-1">
          <li v-for="item in group.items" :key="item.href">
            <Link
              v-if="!item.future"
              :href="item.href"
              :class="itemClass(isActive(item.href), collapsed)"
              :aria-current="isActive(item.href) ? 'page' : undefined"
              @click="emit('close')"
            >
              <span class="absolute left-0 top-2 h-7 w-0.5 origin-center rounded-pill bg-primary transition-transform duration-200 ease-out dark:bg-dark-primary motion-reduce:transition-none" :class="isActive(item.href) ? 'scale-y-100' : 'scale-y-0'" aria-hidden="true" />
              <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
              <span class="truncate" :class="collapsed ? 'lg:sr-only' : ''">{{ item.label }}</span>
              <span v-if="collapsed" :class="tooltipClass" aria-hidden="true">{{ item.label }}</span>
            </Link>
            <span v-else :class="[itemClass(false, collapsed), 'cursor-not-allowed opacity-60 hover:bg-transparent']" aria-disabled="true">
              <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
              <span class="truncate" :class="collapsed ? 'lg:sr-only' : ''">{{ item.label }}</span>
              <span v-if="!collapsed" class="ml-auto rounded-pill bg-muted/10 px-2 py-0.5 text-caption text-muted dark:bg-dark-surface-2 dark:text-dark-muted">Soon</span>
              <span v-else :class="tooltipClass" aria-hidden="true">{{ item.label }} — coming soon</span>
            </span>
          </li>
        </ul>
      </section>
    </nav>

    <div class="hidden shrink-0 border-t border-border-default p-3 dark:border-dark-border lg:block">
      <button
        type="button"
        class="group relative flex min-h-10 w-full items-center gap-3 rounded-lg px-3 text-small font-medium text-muted transition-colors duration-150 hover:bg-background hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink"
        :class="collapsed ? 'justify-center px-0' : ''"
        :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        :aria-expanded="!collapsed"
        aria-controls="app-sidebar"
        @click="emit('toggle-collapse')"
      >
        <component :is="collapsed ? ChevronsRight : ChevronsLeft" class="h-5 w-5 shrink-0" aria-hidden="true" />
        <span v-if="!collapsed">Collapse</span>
        <span v-else :class="tooltipClass" aria-hidden="true">Expand sidebar</span>
      </button>
    </div>
  </aside>
</template>
