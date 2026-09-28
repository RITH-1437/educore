<script setup>
import { Link } from '@inertiajs/vue3'
import { Menu, X } from '@lucide/vue'
import { onMounted, onBeforeUnmount, ref } from 'vue'
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

const onScroll = () => {
  scrolled.value = window.scrollY > 12
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))

const go = (id) => {
  open.value = false
  scrollToId(id)
}
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
    :class="scrolled ? 'border-b border-slate-200 bg-white/90 shadow-sm backdrop-blur-md' : 'bg-transparent'"
  >
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-18 sm:px-6 lg:px-8">
      <a
        href="#top"
        class="flex items-center gap-2.5"
        aria-label="EduCore — back to top"
        @click.prevent="scrollToId('top', 0)"
      >
        <img :src="symbolMark" alt="" class="h-8 w-8 rounded-lg object-cover" />
        <img :src="wordmark" alt="EduCore" class="h-7 w-auto" />
      </a>

      <div class="hidden items-center gap-8 lg:flex">
        <button
          v-for="link in links"
          :key="link.id"
          type="button"
          class="text-sm font-medium text-slate-700 transition hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
          @click="go(link.id)"
        >
          {{ link.label }}
        </button>
      </div>

      <div class="hidden items-center gap-3 lg:flex">
        <Link
          href="/login"
          class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
        >
          Sign In
        </Link>
        <button
          type="button"
          class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
          @click="go('platform')"
        >
          Explore EduCore
        </button>
      </div>

      <button
        type="button"
        class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 lg:hidden"
        :aria-expanded="open"
        aria-label="Toggle navigation menu"
        @click="open = !open"
      >
        <X v-if="open" class="h-6 w-6" />
        <Menu v-else class="h-6 w-6" />
      </button>
    </nav>

    <div v-if="open" class="border-t border-slate-200 bg-white lg:hidden">
      <div class="mx-auto max-w-7xl space-y-1 px-4 py-4 sm:px-6">
        <button
          v-for="link in links"
          :key="link.id"
          type="button"
          class="block w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600"
          @click="go(link.id)"
        >
          {{ link.label }}
        </button>
        <div class="flex gap-3 pt-2">
          <Link
            href="/login"
            class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700"
          >
            Sign In
          </Link>
          <button
            type="button"
            class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
            @click="go('platform')"
          >
            Explore EduCore
          </button>
        </div>
      </div>
    </div>
  </header>
</template>