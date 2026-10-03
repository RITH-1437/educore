<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ChevronsLeft, ChevronsRight, X } from '@lucide/vue'
import { useNavigation } from '../../composables/useNavigation'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  mobileOpen: { type: Boolean, default: false },
  /** True when the drawer is off-screen and must not be reachable by keyboard. */
  hidden: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle-collapse', 'close'])
const page = usePage()
const { navGroups, homeHref, isActive } = useNavigation()
const closeButton = ref(null)
const nav = ref(null)
const appIcon = '/assets/logo/educore-favicon.svg'

defineExpose({ focusClose: () => closeButton.value?.focus() })

const itemClass = (active, collapsed) => [
  'group relative flex min-h-11 items-center gap-3 rounded-lg px-3 text-small font-medium transition-colors duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none dark:focus-visible:outline-dark-primary',
  active
    ? 'bg-primary/5 font-semibold text-primary dark:bg-dark-primary/10 dark:text-dark-primary'
    : 'text-muted hover:bg-background hover:text-ink dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink',
  collapsed ? 'lg:justify-center lg:px-0' : '',
]

// Collapsed labels: one tooltip for the whole sidebar, placed beside the
// hovered or keyboard-focused item. It sits outside the scrolling nav, so the
// nav scrolls in both states without clipping it.
const tip = ref(null)
let tipTarget = null
const hideTip = () => {
  tip.value = null
  tipTarget = null
}
// Follows its item while the nav scrolls; hides once the item leaves the view.
const placeTip = () => {
  if (!tipTarget || !tip.value) return
  const aside = tipTarget.closest('aside').getBoundingClientRect()
  const item = tipTarget.getBoundingClientRect()
  const view = nav.value?.contains(tipTarget) ? nav.value.getBoundingClientRect() : aside
  const center = item.top + item.height / 2
  if (center < view.top || center > view.bottom) return hideTip()
  tip.value = { ...tip.value, top: center - aside.top }
}
const showTip = (event, text) => {
  const target = event.currentTarget
  if (!props.collapsed || (event.type === 'focus' && !target.matches(':focus-visible'))) return
  tipTarget = target
  tip.value = { text, top: 0 }
  placeTip()
}

// Keep the current page's item in view: on load, after navigating from a link
// elsewhere, and after collapsing or expanding.
const revealActive = () => {
  const item = nav.value?.querySelector('[aria-current="page"]')
  if (!item) return
  const box = item.getBoundingClientRect()
  const view = nav.value.getBoundingClientRect()
  if (box.top < view.top || box.bottom > view.bottom) {
    nav.value.scrollTop += box.top - view.top - (view.height - box.height) / 2
  }
}

onMounted(revealActive)
watch(() => page.url, () => nextTick(revealActive))
watch(() => props.collapsed, () => {
  hideTip()
  nextTick(revealActive)
})
</script>

<template>
  <aside
    id="app-sidebar"
    :inert="hidden"
    class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col glass-surface border-r border-border-default transition-[width,transform] duration-300 ease-out dark:border-dark-border lg:translate-x-0 motion-reduce:transition-none"
    :class="[collapsed ? 'lg:w-20' : 'lg:w-72', mobileOpen ? 'translate-x-0 shadow-lg' : '-translate-x-full lg:translate-x-0']"
    aria-label="Main sidebar"
  >
    <div class="flex h-16 shrink-0 items-center justify-between overflow-hidden px-4" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
      <Link :href="homeHref" class="flex min-w-0 items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" aria-label="EduCore home">
        <img :src="appIcon" alt="" class="h-9 w-9 shrink-0 rounded-lg" width="36" height="36" />
        <span v-if="!collapsed" class="min-w-0">
          <span class="block truncate font-display text-body font-semibold leading-5 text-ink dark:text-dark-ink">EduCore</span>
          <span class="block truncate text-caption text-muted dark:text-dark-muted">Academic administration</span>
        </span>
      </Link>
      <button ref="closeButton" type="button" class="rounded-md p-2 text-muted hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:hidden" aria-label="Close navigation" @click="emit('close')">
        <X class="h-5 w-5" aria-hidden="true" />
      </button>
    </div>

    <!-- Scrolls on its own in both states; the page behind never scrolls with it. -->
    <nav
      ref="nav"
      class="min-h-0 flex-1 space-y-6 overflow-y-auto overflow-x-hidden overscroll-contain px-3 py-4"
      :class="collapsed ? 'no-scrollbar' : 'scrollbar-thin'"
      aria-label="Primary navigation"
      @scroll.passive="placeTip"
    >
      <section v-for="group in navGroups" :key="group.label">
        <h2 v-if="!collapsed" class="mb-2 truncate px-3 text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ group.label }}</h2>
        <div v-else class="mx-3 mb-2 hidden border-t border-border-default dark:border-dark-border lg:block" aria-hidden="true" />
        <ul class="space-y-1">
          <li v-for="item in group.items" :key="item.href">
            <Link
              v-if="!item.future"
              :href="item.href"
              :class="itemClass(isActive(item.href), collapsed)"
              :aria-current="isActive(item.href) ? 'page' : undefined"
              @click="emit('close')"
              @mouseenter="showTip($event, item.label)"
              @mouseleave="hideTip"
              @focus="showTip($event, item.label)"
              @blur="hideTip"
            >
              <span class="absolute left-0 top-2 h-7 w-0.5 origin-center rounded-pill bg-primary transition-transform duration-200 ease-out dark:bg-dark-primary motion-reduce:transition-none" :class="isActive(item.href) ? 'scale-y-100' : 'scale-y-0'" aria-hidden="true" />
              <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
              <span class="truncate" :class="collapsed ? 'lg:sr-only' : ''">{{ item.label }}</span>
            </Link>
            <span v-else :class="[itemClass(false, collapsed), 'cursor-not-allowed opacity-60 hover:bg-transparent']" aria-disabled="true" @mouseenter="showTip($event, `${item.label} — coming soon`)" @mouseleave="hideTip">
              <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
              <span class="truncate" :class="collapsed ? 'lg:sr-only' : ''">{{ item.label }}</span>
              <span v-if="!collapsed" class="ml-auto rounded-pill bg-muted/10 px-2 py-0.5 text-caption text-muted dark:bg-dark-surface-2 dark:text-dark-muted">Soon</span>
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
        @mouseenter="showTip($event, 'Expand sidebar')"
        @mouseleave="hideTip"
        @focus="showTip($event, 'Expand sidebar')"
        @blur="hideTip"
      >
        <component :is="collapsed ? ChevronsRight : ChevronsLeft" class="h-5 w-5 shrink-0" aria-hidden="true" />
        <span v-if="!collapsed">Collapse</span>
      </button>
    </div>

    <Transition enter-active-class="transition-opacity duration-150 ease-out motion-reduce:transition-none" enter-from-class="opacity-0">
      <span
        v-if="collapsed && tip"
        class="pointer-events-none absolute left-full z-50 ml-3 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-primary-dark px-3 py-2 text-caption font-medium text-white shadow-md lg:block"
        :style="{ top: `${tip.top}px` }"
        aria-hidden="true"
      >{{ tip.text }}</span>
    </Transition>
  </aside>
</template>
