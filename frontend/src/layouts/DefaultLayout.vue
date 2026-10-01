<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppSidebar from '../components/layout/AppSidebar.vue'
import AppTopbar from '../components/layout/AppTopbar.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useNavigation } from '../composables/useNavigation'
import { useTheme } from '../composables/useTheme'

const COLLAPSE_KEY = 'educore_sidebar_collapsed'
const FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'

const page = usePage()
const { pageTitle } = useNavigation()
const { theme, toggle: toggleTheme } = useTheme()

const sidebar = ref(null)
const collapsed = ref(false)
const mobileOpen = ref(false)
const desktop = ref(true)
const navigating = ref(false)
let desktopQuery = null
let opener = null
const removers = []

// Re-run the entrance animation only when the page path changes, not for
// search/pagination query updates on the same page.
const pageKey = computed(() => page.url.split('?')[0])

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

onMounted(() => {
  try {
    collapsed.value = localStorage.getItem(COLLAPSE_KEY) === '1'
  } catch {
    collapsed.value = false
  }

  desktopQuery = window.matchMedia('(min-width: 1024px)')
  desktop.value = desktopQuery.matches
  const onChange = (event) => {
    desktop.value = event.matches
    if (event.matches) closeDrawer()
  }
  desktopQuery.addEventListener('change', onChange)
  removers.push(() => desktopQuery.removeEventListener('change', onChange))

  window.addEventListener('keydown', onKeydown)
  removers.push(
    () => window.removeEventListener('keydown', onKeydown),
    router.on('start', () => { navigating.value = true }),
    router.on('finish', () => { navigating.value = false }),
    router.on('navigate', () => { mobileOpen.value = false }),
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
        <div v-if="page.props.flash?.success" class="mb-6 rounded-lg border border-success/20 bg-success/5 px-4 py-3 text-small text-success motion-safe:animate-slide-down dark:text-green-300" role="status">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="mb-6 rounded-lg border border-error/20 bg-error/5 px-4 py-3 text-small text-error motion-safe:animate-slide-down dark:text-red-300" role="alert">{{ page.props.flash.error }}</div>
        <div :key="pageKey" class="motion-safe:animate-page-in transition-opacity duration-200 ease-out" :class="navigating ? 'opacity-70' : ''">
          <slot />
        </div>
      </main>
    </div>

    <ConfirmDialog />
  </div>
</template>
