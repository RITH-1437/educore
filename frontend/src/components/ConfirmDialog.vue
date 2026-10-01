<script setup>
import { ref, watch } from 'vue'
import { TriangleAlert } from '@lucide/vue'
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'
import { confirmState, settleConfirm } from '../composables/useConfirm'

const footer = ref(null)

// BaseModal focuses its first control (the close button); move focus to the
// safe choice (Cancel) once it has done so.
watch(
  () => confirmState.open,
  (open) => {
    if (!open) return
    requestAnimationFrame(() => requestAnimationFrame(() => footer.value?.querySelector('[data-cancel]')?.focus()))
  },
)

const onModelUpdate = (value) => {
  if (!value) settleConfirm(false)
}
</script>

<template>
  <BaseModal :model-value="confirmState.open" :title="confirmState.title" size="sm" @update:model-value="onModelUpdate">
    <div class="flex items-start gap-3">
      <span
        v-if="confirmState.destructive"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-error/10 text-error dark:bg-error/15 dark:text-red-300"
      >
        <TriangleAlert class="h-5 w-5" aria-hidden="true" />
      </span>
      <p class="text-small text-muted dark:text-dark-muted">{{ confirmState.message }}</p>
    </div>

    <template #footer>
      <div ref="footer" class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <BaseButton variant="secondary" data-cancel @click="settleConfirm(false)">{{ confirmState.cancelLabel }}</BaseButton>
        <BaseButton :variant="confirmState.destructive ? 'danger' : 'primary'" @click="settleConfirm(true)">{{ confirmState.confirmLabel }}</BaseButton>
      </div>
    </template>
  </BaseModal>
</template>
