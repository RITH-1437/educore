<script setup>
import CampusMap from '../components/CampusMap.vue'
</script>

<template>
  <div class="landing min-h-screen bg-surface text-ink antialiased dark:bg-dark-surface dark:text-dark-ink">
    <slot />
    <CampusMap />
  </div>
</template>
