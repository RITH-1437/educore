<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
  BookOpen,
  CalendarDays,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  GraduationCap,
  LayoutDashboard,
  LogOut,
  Menu,
  ShieldCheck,
  Users,
  X,
} from '@lucide/vue'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const role = computed(() => user.value?.role?.slug ?? '')
const collapsed = ref(false)
const mobileOpen = ref(false)
const accountOpen = ref(false)
const pageTitle = computed(() => {
  const path = page.url.split('?')[0]
  if (path === '/admin/dashboard') return 'Dashboard'
  if (path.startsWith('/users')) return 'User Management'
  if (path.startsWith('/academic-years')) return 'Academic Calendar'
  if (path === '/dashboard') return 'Dashboard'
  return 'Overview'
})

const navGroups = computed(() => {
  if (role.value === 'super-admin') {
    return [
      {
        label: 'Overview',
        items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }],
      },
      {
        label: 'Platform management',
        items: [
          { label: 'Users & roles', href: '/users', icon: Users },
          { label: 'Academic years', href: '/academic-years', icon: CalendarDays },
          { label: 'Faculties & departments', href: '#faculties', icon: GraduationCap, future: true },
          { label: 'Programs & courses', href: '#programs', icon: BookOpen, future: true },
          { label: 'Security & access', href: '#security', icon: ShieldCheck, future: true },
        ],
      },
    ]
  }

  return [{
    label: 'Workspace',
    items: [{ label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard }],
  }]
})

const isActive = (href) => page.url.split('?')[0] === href || page.url.startsWith(`${href}/`)
const logout = () => router.post('/logout')

const onKeydown = (event) => {
  if (event.key === 'Escape') {
    mobileOpen.value = false
    accountOpen.value = false
  }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Head>
    <title>{{ pageTitle }} - EduCore</title>
    <meta name="theme-color" content="#F8FAFC" />
  </Head>

  <div class="min-h-screen bg-background text-ink antialiased dark:bg-dark-bg dark:text-dark-ink">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <button
        v-if="mobileOpen"
        class="fixed inset-0 z-40 cursor-default bg-primary-dark/50 lg:hidden"
        aria-label="Close navigation"
        @click="mobileOpen = false"
      />
    </Transition>

    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-border-default bg-surface transition-[width,transform] duration-200 ease-out dark:border-dark-border dark:bg-dark-surface lg:translate-x-0 motion-reduce:transition-none"
      :class="[
        collapsed ? 'lg:w-20' : 'lg:w-72',
        mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
      aria-label="Main sidebar"
    >
      <div class="flex h-16 shrink-0 items-center justify-between border-b border-border-default px-4 dark:border-dark-border">
        <Link :href="role === 'super-admin' ? '/admin/dashboard' : '/dashboard'" class="flex min-w-0 items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-primary">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-small font-bold text-white">EC</span>
          <span v-if="!collapsed" class="min-w-0">
            <span class="block font-display text-body font-semibold text-ink dark:text-dark-ink">EduCore</span>
            <span class="block text-caption text-muted dark:text-dark-muted">Academic administration</span>
          </span>
        </Link>
        <button class="rounded-md p-2 text-muted hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:hidden" aria-label="Close menu" @click="mobileOpen = false">
          <X class="h-5 w-5" aria-hidden="true" />
        </button>
        <button v-if="!collapsed" class="hidden rounded-md p-2 text-muted hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:inline-flex" aria-label="Collapse sidebar" @click="collapsed = true">
          <ChevronLeft class="h-5 w-5" aria-hidden="true" />
        </button>
        <button v-else class="hidden rounded-md p-2 text-muted hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:inline-flex" aria-label="Expand sidebar" @click="collapsed = false">
          <ChevronRight class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <nav class="min-h-0 flex-1 space-y-7 overflow-y-auto px-3 py-6" aria-label="Primary navigation">
        <section v-for="group in navGroups" :key="group.label">
          <h2 v-if="!collapsed" class="mb-2 px-3 text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ group.label }}</h2>
          <ul class="space-y-1">
            <li v-for="item in group.items" :key="item.href">
              <Link
                v-if="!item.future"
                :href="item.href"
                :class="[
                  'group flex min-h-11 items-center gap-3 rounded-lg border-l-2 px-3 text-small font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
                  isActive(item.href)
                    ? 'border-primary bg-primary/5 text-primary dark:bg-dark-primary/10 dark:text-dark-primary'
                    : 'border-transparent text-muted hover:bg-background hover:text-ink dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink',
                  collapsed ? 'lg:justify-center lg:px-0' : '',
                ]"
                :title="collapsed ? item.label : undefined"
                @click="mobileOpen = false"
              >
                <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span class="truncate" :class="collapsed ? 'lg:sr-only' : ''">{{ item.label }}</span>
              </Link>
              <span v-else class="group flex min-h-11 items-center gap-3 rounded-lg px-3 text-small text-muted/60 dark:text-dark-muted/60" :title="`${item.label} — coming soon`">
                <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span class="truncate" :class="collapsed ? 'lg:sr-only' : ''">{{ item.label }}</span>
                <span v-if="!collapsed" class="ml-auto text-caption">Soon</span>
              </span>
            </li>
          </ul>
        </section>
      </nav>

      <div class="border-t border-border-default p-3 dark:border-dark-border">
        <Link href="/" class="flex items-center gap-3 rounded-lg px-3 py-2 text-small text-muted hover:bg-background hover:text-ink dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-pill bg-primary/10 text-small font-semibold text-primary dark:bg-dark-primary/15 dark:text-dark-primary">{{ user?.name?.slice(0, 1)?.toUpperCase() ?? 'U' }}</span>
          <span v-if="!collapsed" class="min-w-0 flex-1">
            <span class="block truncate font-medium text-ink dark:text-dark-ink">{{ user?.name }}</span>
            <span class="block truncate text-caption text-muted dark:text-dark-muted">{{ user?.role?.name }}</span>
          </span>
        </Link>
      </div>
    </aside>

    <div class="min-h-screen transition-[padding] duration-200 ease-out motion-reduce:transition-none" :class="collapsed ? 'lg:pl-20' : 'lg:pl-72'">
      <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-border-default bg-surface/95 px-4 backdrop-blur sm:px-6 lg:px-8 dark:border-dark-border dark:bg-dark-surface/95">
        <div class="flex min-w-0 items-center gap-3">
          <button class="rounded-md p-2 text-muted hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 lg:hidden" aria-label="Open navigation" @click="mobileOpen = true">
            <Menu class="h-5 w-5" aria-hidden="true" />
          </button>
          <div class="min-w-0">
            <p class="text-caption text-muted dark:text-dark-muted">EduCore / {{ user?.role?.name ?? 'Account' }}</p>
            <h1 class="truncate text-h4 font-semibold text-ink dark:text-dark-ink">{{ pageTitle }}</h1>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <span class="hidden rounded-pill bg-background px-3 py-1.5 text-caption text-muted dark:bg-dark-surface-2 dark:text-dark-muted md:inline-flex">{{ user?.role?.name }}</span>
          <div class="relative">
            <button class="flex min-h-10 items-center gap-2 rounded-lg px-2 text-small text-ink hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:bg-dark-surface-2" :aria-expanded="accountOpen" aria-haspopup="menu" @click="accountOpen = !accountOpen">
              <span class="flex h-8 w-8 items-center justify-center rounded-pill bg-primary text-caption font-semibold text-white">{{ user?.name?.slice(0, 1)?.toUpperCase() ?? 'U' }}</span>
              <span class="hidden max-w-36 truncate sm:inline">{{ user?.name }}</span>
              <ChevronDown class="hidden h-4 w-4 text-muted sm:block" aria-hidden="true" />
            </button>
            <div v-if="accountOpen" class="absolute right-0 top-full z-50 mt-2 w-56 rounded-lg border border-border-default bg-surface p-2 shadow-md dark:border-dark-border dark:bg-dark-surface" role="menu">
              <div class="border-b border-border-default px-3 py-2 dark:border-dark-border">
                <p class="truncate text-small font-medium text-ink dark:text-dark-ink">{{ user?.name }}</p>
                <p class="truncate text-caption text-muted dark:text-dark-muted">{{ user?.email }}</p>
              </div>
              <button type="button" class="mt-2 flex min-h-10 w-full items-center gap-2 rounded-md px-3 text-small text-error hover:bg-error/5 focus-visible:outline-2 focus-visible:outline-error" role="menuitem" @click="logout">
                <LogOut class="h-4 w-4" aria-hidden="true" /> Sign out
              </button>
            </div>
          </div>
        </div>
      </header>

      <main class="mx-auto w-full max-w-[1280px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div v-if="page.props.flash?.success" class="mb-6 rounded-lg border border-success/20 bg-success/5 px-4 py-3 text-small text-success" role="status">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="mb-6 rounded-lg border border-error/20 bg-error/5 px-4 py-3 text-small text-error" role="alert">{{ page.props.flash.error }}</div>
        <slot />
      </main>
    </div>
  </div>
</template>
