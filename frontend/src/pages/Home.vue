<template>
  <section>
    <div class="mb-6 flex items-center justify-between">
      <div>
        {{ user?.role?.name ?? '—' }}
      </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <p class="text-sm text-gray-500">Account</p>
        <p class="mt-1 text-lg font-semibold text-gray-900">{{ user ? user.email : 'Guest' }}</p>
      </div>
      <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <p class="text-sm text-gray-500">Access role</p>
        <p class="mt-1 text-lg font-semibold text-gray-900">{{ user?.role?.name ?? '—' }}</p>
      </div>
      <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <p class="text-sm text-gray-500">Status</p>
        <p class="mt-1 text-lg font-semibold text-gray-900">
          {{ user ? (user.is_active ? 'Active' : 'Inactive') : '—' }}
        </p>
      </div>
      <Link href="/users" v-if="user" class="group rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-indigo-300">
        <p class="text-sm text-gray-500">User management</p>
        <p class="mt-1 text-lg font-semibold text-indigo-600 group-hover:underline">Manage accounts →</p>
      </Link>
    </div>
  </section>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const user = computed(() => usePage().props.auth?.user ?? null)
</script>