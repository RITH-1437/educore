<template>
  <main class="min-h-screen bg-gray-50">
    <header class="border-b border-gray-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
        <Link href="/">
          <h1 class="text-xl font-semibold text-gray-900">EduCore</h1>
        </Link>

        <nav class="flex items-center gap-6 text-sm text-gray-600">
          <Link href="/">Home</Link>

          <template v-if="user">
            <Link v-if="isAdmin" :href="'/users'" class="font-medium text-gray-700 hover:text-gray-900">Users</Link>

            <span class="flex items-center gap-2">
              <span>{{ user.name }}</span>
              <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ user.role?.name }}</span>
            </span>

            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="logout">
              Sign out
            </button>
          </template>

          <Link v-else href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</Link>
        </nav>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-8">
      <slot />
    </div>
  </main>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const isAdmin = computed(() => user.value?.role?.slug === 'super-admin')

const logout = () => router.post('/logout')
</script>