<script setup>
import CampusMap from '../components/CampusMap.vue'
</script>

<template>
  <main class="min-h-screen bg-background text-ink antialiased dark:bg-dark-bg dark:text-dark-ink">
    <slot />
  </main>
  <CampusMap />
</template>
