<script setup>
import { Link } from '@inertiajs/vue3'
import { Menu, X } from '@lucide/vue'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'
const wordmark = '/assets/logo/educore-workmark.png'

const links = [
  { id: 'platform', label: 'Platform' },
  { id: 'modules', label: 'Modules' },
  { id: 'structure', label: 'Structure' },
  { id: 'documents', label: 'Documents' },
  { id: 'security', label: 'Security' },
  { id: 'technology', label: 'Technology' },
]

const open = ref(false)
const scrolled = ref(false)
const activeId = ref('')
const menuButton = ref(null)
const mobileMenu = ref(null)
let observer = null

const onScroll = () => {
  scrolled.value = window.scrollY > 12
}

const onKeydown = (event) => {
  if (event.key === 'Escape' && open.value) {
    open.value = false
    menuButton.value?.focus()
  }
}

const go = (id) => {
  open.value = false
  scrollToId(id)
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
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)

  // Highlight the link of the section crossing the upper third of the viewport.
  observer = new IntersectionObserver(
    (entries) => {
      const hit = entries.find((entry) => entry.isIntersecting)
      if (hit) activeId.value = hit.target.id
    },
    { rootMargin: '-30% 0px -60% 0px' }
  )
  links.forEach((link) => {
    const el = document.getElementById(link.id)
    if (el) observer.observe(el)
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onKeydown)
  observer?.disconnect()
  document.body.style.overflow = ''
})
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
    :class="scrolled || open ? 'border-b border-slate-200/70 bg-white/70 shadow-sm backdrop-blur-xl' : 'bg-transparent'"
  >
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Primary">
      <a
        href="#top"
        class="flex items-center gap-2.5 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
        aria-label="EduCore — back to top"
        @click.prevent="go('top')"
      >
        <img :src="symbolMark" alt="" width="32" height="32" class="h-8 w-8 rounded-lg object-cover" />
        <img :src="wordmark" alt="EduCore" height="28" class="h-7 w-auto" />
      </a>

      <ul class="hidden items-center gap-1 lg:flex">
        <li v-for="link in links" :key="link.id">
          <a
            :href="`#${link.id}`"
            class="relative inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
            :class="activeId === link.id ? 'text-blue-600' : 'text-slate-700 hover:text-blue-600'"
            :aria-current="activeId === link.id ? 'location' : undefined"
            @click.prevent="go(link.id)"
          >
            {{ link.label }}
            <span
              class="absolute inset-x-3 bottom-1.5 h-0.5 rounded-full bg-blue-600 transition-transform duration-200 ease-out motion-reduce:transition-none"
              :class="activeId === link.id ? 'scale-x-100' : 'scale-x-0'"
              aria-hidden="true"
            />
          </a>
        </li>
      </ul>

      <div class="hidden items-center gap-3 lg:flex">
        <Link
          href="/login"
          class="inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 active:scale-[0.98]"
        >
          Sign In
        </Link>
        <a
          href="#platform"
          class="inline-flex min-h-11 items-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 active:scale-[0.98]"
          @click.prevent="go('platform')"
        >
          Explore EduCore
        </a>
      </div>

      <button
        ref="menuButton"
        type="button"
        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 lg:hidden"
        :aria-expanded="open"
        aria-controls="landing-mobile-menu"
        :aria-label="open ? 'Close navigation menu' : 'Open navigation menu'"
        @click="open = !open"
      >
        <X v-if="open" class="h-6 w-6" aria-hidden="true" />
        <Menu v-else class="h-6 w-6" aria-hidden="true" />
      </button>
    </nav>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-out"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-2 opacity-0"
    >
      <div v-if="open" id="landing-mobile-menu" ref="mobileMenu" class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-slate-200 bg-white lg:hidden">
        <ul class="mx-auto max-w-7xl space-y-1 px-4 pt-3 sm:px-6">
          <li v-for="link in links" :key="link.id">
            <a
              :href="`#${link.id}`"
              class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
              :class="activeId === link.id ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50 hover:text-blue-600'"
              :aria-current="activeId === link.id ? 'location' : undefined"
              @click.prevent="go(link.id)"
            >
              {{ link.label }}
            </a>
          </li>
        </ul>
        <div class="mx-auto flex max-w-7xl gap-3 px-4 py-4 sm:px-6">
          <Link
            href="/login"
            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
          >
            Sign In
          </Link>
          <a
            href="#platform"
            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
            @click.prevent="go('platform')"
          >
            Explore EduCore
          </a>
        </div>
      </div>
    </Transition>
  </header>
</template>
