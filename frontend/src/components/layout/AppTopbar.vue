<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Bell, CalendarDays, ChevronRight, KeyRound, LogOut, Menu, Moon, Sun } from '@lucide/vue'
import BaseDropdown from '../BaseDropdown.vue'
import { useNavigation } from '../../composables/useNavigation'

defineProps({
  theme: { type: String, default: 'light' },
})

const emit = defineEmits(['open-navigation', 'toggle-theme'])
const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const initial = computed(() => user.value?.name?.slice(0, 1)?.toUpperCase() ?? 'U')
const { breadcrumbs } = useNavigation()

const todayLabel = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const logout = () => router.post('/logout')
</script>

<template>
  <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 glass-surface px-4 sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-2">
      <button type="button" class="-ml-2 inline-flex h-11 w-11 items-center justify-center rounded-md text-muted hover:bg-surface focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:hidden" aria-label="Open navigation" aria-controls="app-sidebar" @click="emit('open-navigation')">
        <Menu class="h-5 w-5" aria-hidden="true" />
      </button>
      <nav aria-label="Breadcrumb" class="min-w-0">
        <ol class="flex min-w-0 items-center gap-1.5 text-small">
          <li v-for="(crumb, index) in breadcrumbs" :key="crumb.label + index" class="flex min-w-0 items-center gap-1.5" :class="index < breadcrumbs.length - 1 && breadcrumbs.length > 2 ? 'max-sm:hidden' : ''">
            <ChevronRight v-if="index > 0" class="h-4 w-4 shrink-0 text-border-muted dark:text-dark-border" aria-hidden="true" />
            <Link v-if="crumb.href && index < breadcrumbs.length - 1" :href="crumb.href" class="truncate rounded-sm text-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:text-dark-ink">{{ crumb.label }}</Link>
            <span v-else class="truncate font-medium text-ink dark:text-dark-ink" :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined">{{ crumb.label }}</span>
          </li>
        </ol>
      </nav>
    </div>

    <span class="hidden items-center gap-1.5 text-caption text-muted dark:text-dark-muted sm:flex">
      <CalendarDays class="h-3.5 w-3.5" aria-hidden="true" />
      {{ todayLabel }}
    </span>

    <div class="flex items-center gap-1 sm:gap-2">
      <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-muted transition-colors duration-150 hover:bg-surface hover:text-ink focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink" :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'" @click="emit('toggle-theme')">
        <Sun v-if="theme === 'dark'" class="h-5 w-5" aria-hidden="true" />
        <Moon v-else class="h-5 w-5" aria-hidden="true" />
      </button>

      <BaseDropdown>
        <template #trigger="{ open, toggle }">
          <button type="button" class="flex min-h-11 items-center gap-2 rounded-md px-2 text-small text-ink transition-colors duration-150 hover:bg-surface focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:bg-dark-surface-2" :aria-expanded="open" aria-haspopup="menu" aria-label="Account menu" @click="toggle">
            <span class="flex h-8 w-8 items-center justify-center rounded-pill bg-primary text-caption font-semibold text-white dark:bg-dark-primary dark:text-dark-bg">{{ initial }}</span>
            <span class="hidden min-w-0 text-left md:block">
              <span class="block max-w-36 truncate text-small font-medium leading-4">{{ user?.name }}</span>
              <span class="block max-w-36 truncate text-caption leading-4 text-muted dark:text-dark-muted">{{ user?.role?.name }}</span>
            </span>
          </button>
        </template>
        <template #content>
          <div class="border-b border-border-default px-4 py-3 dark:border-dark-border" role="presentation">
            <p class="truncate text-small font-medium text-ink dark:text-dark-ink">{{ user?.name }}</p>
            <p class="truncate text-caption text-muted dark:text-dark-muted">{{ user?.email }}</p>
          </div>
          <div class="p-1" role="menu">
            <Link href="/notifications" class="flex min-h-11 w-full items-center gap-2 rounded-md px-3 text-small text-ink hover:bg-surface focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:bg-dark-surface-2" role="menuitem">
              <Bell class="h-4 w-4" aria-hidden="true" /> Notification settings
            </Link>
            <Link href="/account/password" class="flex min-h-11 w-full items-center gap-2 rounded-md px-3 text-small text-ink hover:bg-surface focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:bg-dark-surface-2" role="menuitem">
              <KeyRound class="h-4 w-4" aria-hidden="true" /> Change password
            </Link>
            <button type="button" class="flex min-h-11 w-full items-center gap-2 rounded-md px-3 text-small text-error hover:bg-error/5 focus-visible:outline-2 focus-visible:outline-error dark:text-red-300 dark:hover:bg-error/10" role="menuitem" @click="logout">
              <LogOut class="h-4 w-4" aria-hidden="true" /> Sign out
            </button>
          </div>
        </template>
      </BaseDropdown>
    </div>
  </header>
</template>
