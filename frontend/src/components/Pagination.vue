<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  links: { type: Array, default: () => [] },
})

const currentPage = () => links.find((l) => l.active)?.label ?? 1
</script>

<template>
  <nav v-if="links.length > 3" class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
      <span v-for="link in links" :key="link.label">
        <Link
          v-if="link.url && ['Previous', 'Next'].includes(link.label)"
          :href="link.url"
          class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          preserve-scroll
        >
          {{ link.label }}
        </Link>
      </span>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <p class="text-sm text-gray-700">Page {{ currentPage() }}</p>
      <div>
        <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <template v-for="link in links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              preserve-scroll
              class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              :class="{ 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600': link.active }"
            >
              {{ link.label }}
            </Link>
            <span v-else class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-400">
              {{ link.label }}
            </span>
          </template>
        </nav>
      </div>
    </div>
  </nav>
</template>