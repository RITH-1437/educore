<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppSidebar from '../components/layout/AppSidebar.vue'
import AppTopbar from '../components/layout/AppTopbar.vue'
import CampusMap from '../components/CampusMap.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ToastRegion from '../components/ToastRegion.vue'
import { useNavigation } from '../composables/useNavigation'
import { useTheme } from '../composables/useTheme'
import { toast } from '../composables/useToast'

const COLLAPSE_KEY = 'educore_sidebar_collapsed'
const FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'

const page = usePage()
const { pageTitle } = useNavigation()
const { theme, toggle: toggleTheme } = useTheme()

const readCollapsed = () => {
  try {
    return localStorage.getItem(COLLAPSE_KEY) === '1'
  } catch {
    return false
  }
}

// Read before the first render: starting expanded and collapsing on mount
// animated the sidebar open and shut on every page load.
const desktopQuery = window.matchMedia('(min-width: 1024px)')
const sidebar = ref(null)
const collapsed = ref(readCollapsed())
const mobileOpen = ref(false)
const desktop = ref(desktopQuery.matches)
const navigating = ref(false)
let opener = null
const removers = []

// Re-run the entrance animation only when the page path changes, not for
// search/pagination query updates on the same page.
const pageKey = computed(() => page.url.split('?')[0])

// Live filters swap the list under the user's cursor. While they do, the page
// keeps at least the height it had, so fewer results never make it jump or
// pull the scroll position; moving to another page releases the hold.
const content = ref(null)
const heldHeight = ref(0)
watch(pageKey, () => { heldHeight.value = 0 })

const persistCollapsed = (value) => {
  collapsed.value = value
  try {
    localStorage.setItem(COLLAPSE_KEY, value ? '1' : '0')
  } catch {
    // preference simply won't persist
  }
}

const openDrawer = () => {
  opener = document.activeElement
  mobileOpen.value = true
}

const closeDrawer = () => {
  if (!mobileOpen.value) return
  mobileOpen.value = false
  opener?.focus?.()
  opener = null
}

const onKeydown = (event) => {
  if (!mobileOpen.value) return
  if (event.key === 'Escape') {
    closeDrawer()
    return
  }
  if (event.key !== 'Tab') return

  const items = [...(document.getElementById('app-sidebar')?.querySelectorAll(FOCUSABLE) ?? [])]
  if (!items.length) return
  const first = items[0]
  const last = items[items.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

// Lock page scroll behind the drawer and move focus into it.
watch(mobileOpen, async (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
  if (open) {
    await nextTick()
    sidebar.value?.focusClose()
  }
})

// Server flash messages become bottom-right toasts. `success` fires once per
// server response (history Back/Forward restores pages without one, so old
// messages never reappear); the first page load is read on mount.
const showFlash = (flash) => {
  toast.success(flash?.success)
  toast.error(flash?.error)
}

onMounted(() => {
  showFlash(page.props.flash)
  const onChange = (event) => {
    desktop.value = event.matches
    if (event.matches) closeDrawer()
  }
  desktopQuery.addEventListener('change', onChange)
  removers.push(() => desktopQuery.removeEventListener('change', onChange))

  window.addEventListener('keydown', onKeydown)
  removers.push(
    () => window.removeEventListener('keydown', onKeydown),
    // Quiet visits (live filters: no progress bar) keep the page as it is; only navigations dim it.
    router.on('start', (event) => {
      const quiet = event.detail.visit.showProgress === false
      navigating.value = !quiet
      if (quiet && content.value) heldHeight.value = Math.max(heldHeight.value, content.value.offsetHeight)
    }),
    router.on('finish', () => { navigating.value = false }),
    router.on('navigate', () => { mobileOpen.value = false }),
    router.on('success', (event) => showFlash(event.detail.page.props.flash)),
  )
})

onBeforeUnmount(() => {
  removers.forEach((remove) => remove())
  document.body.style.overflow = ''
})
</script>

<template>
  <Head>
    <title>{{ pageTitle }} - EduCore</title>
  </Head>

  <div class="relative isolate min-h-screen bg-background text-ink antialiased dark:bg-dark-bg dark:text-dark-ink">
    <div class="app-ambient pointer-events-none fixed inset-0 -z-10" aria-hidden="true" />

    <a href="#main-content" class="sr-only z-[70] rounded-md bg-surface px-4 py-2 text-small font-medium text-primary shadow-md focus:not-sr-only focus:fixed focus:left-4 focus:top-4 dark:bg-dark-surface dark:text-dark-primary">Skip to content</a>

    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-out"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-primary-dark/50 lg:hidden" aria-hidden="true" @click="closeDrawer" />
    </Transition>

    <AppSidebar
      ref="sidebar"
      :collapsed="collapsed && desktop"
      :mobile-open="mobileOpen"
      :hidden="!desktop && !mobileOpen"
      @toggle-collapse="persistCollapsed(!collapsed)"
      @close="closeDrawer"
    />

    <div class="min-h-screen transition-[padding] duration-300 ease-out motion-reduce:transition-none" :class="collapsed ? 'lg:pl-20' : 'lg:pl-72'">
      <AppTopbar :theme="theme" @open-navigation="openDrawer" @toggle-theme="toggleTheme" />

      <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-[1280px] px-4 pb-12 pt-4 focus:outline-none sm:px-6 lg:px-8 lg:pt-6" :aria-busy="navigating">
        <div ref="content" :key="pageKey" class="motion-safe:animate-page-in transition-opacity duration-200 ease-out" :class="navigating ? 'opacity-70' : ''" :style="heldHeight ? { minHeight: `${heldHeight}px` } : undefined">
          <slot />
        </div>
      </main>
    </div>

    <CampusMap />
    <ConfirmDialog />
    <ToastRegion />
  </div>
</template>
