<script setup>
import { ref, watch } from 'vue'
import { CircleAlert, X } from '@lucide/vue'

const props = defineProps({
  title: { type: String, default: 'Something went wrong' },
  message: { type: String, default: '' },
  dismissible: { type: Boolean, default: false },
})

const visible = ref(true)
watch(() => props.message, () => { visible.value = true })
</script>

<template>
  <div v-if="visible && message" class="flex items-start gap-3 rounded-lg border border-error/25 bg-error/5 p-4 text-error dark:border-red-300/25 dark:bg-error/10 dark:text-red-200" role="alert">
    <CircleAlert class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
    <div class="min-w-0 flex-1">
      <p class="text-small font-semibold">{{ title }}</p>
      <p class="mt-1 text-small">{{ message }}</p>
      <div v-if="$slots.default" class="mt-2"><slot /></div>
    </div>
    <button v-if="dismissible" type="button" class="rounded-md p-1 hover:bg-error/10 focus-visible:outline-2 focus-visible:outline-primary" aria-label="Dismiss alert" @click="visible = false">
      <X class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>
