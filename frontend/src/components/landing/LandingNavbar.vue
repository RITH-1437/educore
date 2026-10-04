<script setup>
import { Menu, Moon, Sun, X } from '@lucide/vue'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import BaseButton from '../BaseButton.vue'
import { useTheme } from '../../composables/useTheme'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

// Same light/dark preference as the dashboard (educore_theme, falls back to the system).
const { theme, toggle: toggleTheme } = useTheme()

const links = [
  { id: 'top', label: 'Home' },
  { id: 'platform', label: 'Platform' },
  { id: 'modules', label: 'Modules' },
  { id: 'technology', label: 'Technology' },
  { id: 'about', label: 'About' },
]

const open = ref(false)
const scrolled = ref(false)
const activeId = ref('top')
const menuButton = ref(null)
const mobileMenu = ref(null)
let frame = 0

// The active link is the last linked section whose top has passed a line 30%
// down the viewport. Sections between two links (Ecosystem, Journey, ...)
// therefore keep the previous link lit, so the highlight always matches the
// part of the page being read: Home → Platform → Modules → Technology → About.
const update = () => {
  frame = 0
  scrolled.value = window.scrollY > 12
  const line = window.innerHeight * 0.3
  let current = 'top'
  for (const link of links) {
    const el = document.getElementById(link.id)
    if (el && el.getBoundingClientRect().top <= line) current = link.id
  }
  activeId.value = current
}

const onScroll = () => {
  if (!frame) frame = requestAnimationFrame(update)
}

const onKeydown = (event) => {
  if (event.key === 'Escape' && open.value) {
    open.value = false
    menuButton.value?.focus()
  }
}

const go = (id) => {
  open.value = false
  scrollToId(id, id === 'top' ? 0 : 88)
}

// Lock page scroll while the mobile menu is open; focus the first item.
watch(open, async (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
  if (isOpen) {
    await nextTick()
    mobileMenu.value?.querySelector('a, button')?.focus()
  }
})

onMounted(() => {
  update()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
  window.removeEventListener('keydown', onKeydown)
  cancelAnimationFrame(frame)
  document.body.style.overflow = ''
})

const focusRing = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary'
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 border-b transition-[background-color,border-color,box-shadow] duration-300 ease-out"
    :class="scrolled || open ? 'border-border-default bg-surface shadow-sm dark:border-dark-border dark:bg-dark-surface' : 'border-transparent bg-transparent'"
  >
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Primary">
      <a
        href="#top"
        class="flex items-center gap-2 rounded-md"
        :class="focusRing"
        aria-label="EduCore — back to top"
        @click.prevent="go('top')"
      >
        <img :src="symbolMark" alt="" width="32" height="32" class="h-8 w-8 rounded-md object-cover" />
        <!-- Live-text wordmark (navy "Edu", blue "Core"): the PNG has a white field that shows on the tinted hero. -->
        <span class="font-display text-h4 font-bold"><span class="text-primary-dark dark:text-dark-ink">Edu</span><span class="text-primary dark:text-dark-primary">Core</span></span>
      </a>

      <ul class="hidden items-center gap-1 lg:flex">
        <li v-for="link in links" :key="link.id">
          <a
            :href="`#${link.id}`"
            class="relative inline-flex min-h-10 items-center rounded-md px-3 text-label transition-colors duration-150"
            :class="[focusRing, activeId === link.id ? 'text-primary dark:text-dark-primary' : 'text-ink hover:text-primary dark:text-dark-ink dark:hover:text-dark-primary']"
            :aria-current="activeId === link.id ? 'location' : undefined"
            @click.prevent="go(link.id)"
          >
            {{ link.label }}
            <span
              class="absolute inset-x-3 bottom-1 h-0.5 rounded-pill bg-primary transition-transform duration-200 ease-out dark:bg-dark-primary"
              :class="activeId === link.id ? 'scale-x-100' : 'scale-x-0'"
              aria-hidden="true"
            />
          </a>
        </li>
      </ul>

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
        <div class="hidden items-center gap-2 lg:flex">
          <BaseButton href="/login" variant="ghost">Sign In</BaseButton>
          <BaseButton @click="go('platform')">Explore Platform</BaseButton>
        </div>

        <button
          ref="menuButton"
          type="button"
          class="inline-flex h-11 w-11 items-center justify-center rounded-md text-ink hover:bg-background lg:hidden dark:text-dark-ink dark:hover:bg-dark-surface-2"
          :class="focusRing"
          :aria-expanded="open"
          aria-controls="landing-mobile-menu"
          :aria-label="open ? 'Close navigation menu' : 'Open navigation menu'"
          @click="open = !open"
        >
          <X v-if="open" class="h-6 w-6" aria-hidden="true" />
          <Menu v-else class="h-6 w-6" aria-hidden="true" />
        </button>
      </div>
    </nav>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-out"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-2 opacity-0"
    >
      <div
        v-if="open"
        id="landing-mobile-menu"
        ref="mobileMenu"
        class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-border-default bg-surface lg:hidden dark:border-dark-border dark:bg-dark-surface"
      >
        <ul class="mx-auto max-w-7xl space-y-1 px-4 pt-3 sm:px-6">
          <li v-for="link in links" :key="link.id">
            <a
              :href="`#${link.id}`"
              class="flex min-h-11 items-center rounded-md px-3 text-label"
              :class="[focusRing, activeId === link.id ? 'bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary' : 'text-ink hover:bg-background hover:text-primary dark:text-dark-ink dark:hover:bg-dark-surface-2 dark:hover:text-dark-primary']"
              :aria-current="activeId === link.id ? 'location' : undefined"
              @click.prevent="go(link.id)"
            >
              {{ link.label }}
            </a>
          </li>
        </ul>
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-3 px-4 py-4 sm:px-6">
          <BaseButton href="/login" variant="secondary" size="lg">Sign In</BaseButton>
          <BaseButton size="lg" @click="go('platform')">Explore Platform</BaseButton>
        </div>
      </div>
    </Transition>
  </header>
</template>
