<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { Moon, Sun } from '@lucide/vue'
import { computed } from 'vue'
import BaseButton from '../components/BaseButton.vue'
import CampusMap from '../components/CampusMap.vue'
import ToastRegion from '../components/ToastRegion.vue'
import { useTheme } from '../composables/useTheme'

// Public pages other than the landing page (the course catalog, report 53):
// the landing navbar's look, with page links instead of section anchors.
const symbolMark = '/assets/logo/educore-symbol-mark.jpg'
const { theme, toggle: toggleTheme } = useTheme()
const page = usePage()
const path = computed(() => page.url.split('?')[0])

const links = [
  { href: '/', label: 'Home' },
  { href: '/catalog', label: 'Courses' },
]
const isActive = (href) => (href === '/' ? path.value === '/' : path.value.startsWith(href))
const focusRing = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:focus-visible:outline-dark-primary'
</script>

<template>
  <div class="landing flex min-h-screen flex-col bg-background text-ink antialiased dark:bg-dark-bg dark:text-dark-ink">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-surface focus:px-4 focus:py-2 focus:shadow-md">Skip to main content</a>

    <header class="sticky top-0 z-50 border-b border-border-default bg-surface/95 backdrop-blur dark:border-dark-border dark:bg-dark-surface/95">
      <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8" aria-label="Primary">
        <div class="flex items-center gap-4">
          <Link href="/" class="flex items-center gap-2 rounded-md" :class="focusRing" aria-label="EduCore home">
            <img :src="symbolMark" alt="" width="32" height="32" class="h-8 w-8 rounded-md object-cover" />
            <span class="font-display text-h4 font-bold max-sm:hidden"><span class="text-primary-dark dark:text-dark-ink">Edu</span><span class="text-primary dark:text-dark-primary">Core</span></span>
          </Link>
          <ul class="flex items-center gap-1">
            <li v-for="link in links" :key="link.href">
              <Link
                :href="link.href"
                class="inline-flex min-h-10 items-center rounded-md px-3 text-label transition-colors duration-150"
                :class="[focusRing, isActive(link.href) ? 'text-primary dark:text-dark-primary' : 'text-ink hover:text-primary dark:text-dark-ink dark:hover:text-dark-primary']"
                :aria-current="isActive(link.href) ? 'page' : undefined"
              >
                {{ link.label }}
              </Link>
            </li>
          </ul>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-md text-muted transition-colors duration-150 hover:bg-background hover:text-ink dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink"
            :class="focusRing"
            :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
            @click="toggleTheme"
          >
            <Sun v-if="theme === 'dark'" class="h-5 w-5" aria-hidden="true" />
            <Moon v-else class="h-5 w-5" aria-hidden="true" />
          </button>
          <BaseButton href="/login">Sign In</BaseButton>
        </div>
      </nav>
    </header>

    <main id="main" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <slot />
    </main>

    <footer class="border-t border-border-default py-6 text-center text-caption text-muted dark:border-dark-border dark:text-dark-muted">
      EduCore — One Platform. Smarter Education.
    </footer>

    <ToastRegion />
    <CampusMap />
  </div>
</template>
