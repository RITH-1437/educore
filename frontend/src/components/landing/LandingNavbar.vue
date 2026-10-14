<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Menu, Moon, Sun, X } from '@lucide/vue'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import BaseButton from '../BaseButton.vue'
import { useTheme } from '../../composables/useTheme'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

// Same light/dark preference as the dashboard (educore_theme, falls back to the system).
const { theme, toggle: toggleTheme } = useTheme()

// In page order, so the active link always moves forward while scrolling down.
const links = [
  { id: 'top', label: 'Home' },
  { id: 'platform', label: 'Platform' },
  { id: 'how-it-works', label: 'How It Works' },
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
let desktop = null

// The active link is the last linked section whose top has passed a line 30%
// down the viewport, so sections between two links (Problem → Solution, the
// student journey, the feature sections) keep the link of the part they belong to.
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

const close = ({ restoreFocus = false } = {}) => {
  open.value = false
  if (restoreFocus) menuButton.value?.focus()
}

const onKeydown = (event) => {
  if (event.key === 'Escape' && open.value) close({ restoreFocus: true })
}

const go = (id) => {
  close()
  scrollToId(id, id === 'top' ? 0 : 80)
}

// While the mobile menu is open the page behind it is locked and inert, so
// Tab stays inside the menu; the first item takes focus.
const background = () => [document.getElementById('main-content'), document.getElementById('site-footer')]
watch(open, async (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
  background().forEach((el) => el?.toggleAttribute('inert', isOpen))
  if (isOpen) {
    await nextTick()
    mobileMenu.value?.querySelector('a, button')?.focus()
  }
})

const onDesktop = (event) => {
  if (event.matches) close()
}

onMounted(() => {
  update()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)
  desktop = window.matchMedia('(min-width: 1280px)')
  desktop.addEventListener('change', onDesktop)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
  window.removeEventListener('keydown', onKeydown)
  desktop?.removeEventListener('change', onDesktop)
  cancelAnimationFrame(frame)
  document.body.style.overflow = ''
  background().forEach((el) => el?.removeAttribute('inert'))
})

const focusRing = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:focus-visible:outline-dark-primary'
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 border-b transition-[background-color,border-color,box-shadow] duration-300 ease-out"
    :class="scrolled || open ? 'border-border-default bg-surface shadow-sm dark:border-dark-border dark:bg-dark-surface' : 'border-transparent bg-transparent'"
  >
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8" aria-label="Primary">
      <a
        href="#top"
        class="flex shrink-0 items-center gap-2 rounded-md"
        :class="focusRing"
        aria-label="EduCore — back to top"
        @click.prevent="go('top')"
      >
        <img :src="symbolMark" alt="" width="32" height="32" class="h-8 w-8 rounded-md object-cover" />
        <!-- Live-text wordmark (navy "Edu", blue "Core"): the PNG has a white field that shows on the tinted hero. -->
        <span class="font-display text-h4 font-bold"><span class="text-primary-dark dark:text-dark-ink">Edu</span><span class="text-primary dark:text-dark-primary">Core</span></span>
      </a>

      <ul class="hidden items-center gap-1 xl:flex">
        <li v-for="link in links" :key="link.id">
          <a
            :href="`#${link.id}`"
            class="relative inline-flex min-h-10 items-center rounded-md px-3 text-label whitespace-nowrap transition-colors duration-150"
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
        <!-- The public course catalog is its own page (report 53). -->
        <li>
          <Link href="/catalog" class="inline-flex min-h-10 items-center rounded-md px-3 text-label text-ink transition-colors duration-150 hover:text-primary dark:text-dark-ink dark:hover:text-dark-primary" :class="focusRing">Courses</Link>
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
        <div class="hidden items-center gap-2 md:flex">
          <BaseButton href="/login" variant="ghost">Sign In</BaseButton>
          <BaseButton @click="go('showcase')">Explore Platform</BaseButton>
        </div>

        <button
          ref="menuButton"
          type="button"
          class="inline-flex h-11 w-11 items-center justify-center rounded-md text-ink hover:bg-background xl:hidden dark:text-dark-ink dark:hover:bg-dark-surface-2"
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
        class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-border-default bg-surface xl:hidden dark:border-dark-border dark:bg-dark-surface"
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
          <li>
            <Link href="/catalog" class="flex min-h-11 items-center rounded-md px-3 text-label text-ink hover:bg-background hover:text-primary dark:text-dark-ink dark:hover:bg-dark-surface-2 dark:hover:text-dark-primary" :class="focusRing">Courses</Link>
          </li>
        </ul>
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-3 px-4 py-4 sm:grid-cols-2 sm:px-6">
          <BaseButton size="lg" @click="go('showcase')">
            Explore Platform
            <ArrowRight class="h-4 w-4" aria-hidden="true" />
          </BaseButton>
          <BaseButton href="/login" variant="secondary" size="lg">Sign In</BaseButton>
        </div>
      </div>
    </Transition>
  </header>
</template>
