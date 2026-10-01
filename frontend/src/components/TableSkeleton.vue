<script setup>
import SkeletonBlock from './SkeletonBlock.vue'

defineProps({
  columns: { type: Number, default: 4 },
  rows: { type: Number, default: 5 },
})
</script>

<template>
  <div role="status" aria-label="Loading records">
    <span class="sr-only">Loading records</span>
    <div v-for="row in rows" :key="row" class="flex h-14 items-center gap-6 border-b border-border-default px-4 last:border-b-0 dark:border-dark-border">
      <SkeletonBlock v-for="column in columns" :key="column" class="h-4 max-w-full flex-1" />
    </div>
  </div>
</template>
