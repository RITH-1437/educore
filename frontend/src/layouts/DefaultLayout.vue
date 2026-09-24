<template>
  <main class="min-h-screen bg-gray-50">
    <header class="border-b border-gray-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
        <h1 class="text-xl font-semibold text-gray-900">EduCore</h1>
        <nav class="flex items-center gap-6 text-sm text-gray-600">
          <RouterLink to="/">Home</RouterLink>
        </nav>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-8">
      <RouterView />
    </div>
  </main>
</template>

<script setup lang="ts">
</script>