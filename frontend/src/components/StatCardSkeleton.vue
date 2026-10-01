<script setup>
import BaseCard from './BaseCard.vue'
import SkeletonBlock from './SkeletonBlock.vue'
</script>

<template>
  <!-- Mirrors StatCard dimensions to avoid layout shift. -->
  <BaseCard aria-busy="true">
    <span class="sr-only" role="status">Loading metric</span>
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <SkeletonBlock class="h-5 w-24" />
        <SkeletonBlock class="mt-2 h-8 w-16" />
        <SkeletonBlock class="mt-1 h-4 w-28" />
      </div>
      <SkeletonBlock class="h-10 w-10 rounded-lg" />
    </div>
    <SkeletonBlock class="mt-4 h-4 w-12" />
  </BaseCard>
</template>
