<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  closeOnEscape: { type: Boolean, default: true },
  closeOnOutsideClick: { type: Boolean, default: true },
  align: { type: String, default: 'end', validator: (value) => ['start', 'end'].includes(value) },
})

const emit = defineEmits(['open', 'close'])
const root = ref(null)
const open = ref(false)

const toggle = () => {
  open.value = !open.value
  emit(open.value ? 'open' : 'close')
}

const close = () => {
  if (!open.value) return
  open.value = false
  emit('close')
}

const onDocumentClick = (event) => {
  if (props.closeOnOutsideClick && root.value && !root.value.contains(event.target)) close()
}

const onDocumentKeydown = (event) => {
  if (props.closeOnEscape && event.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onDocumentKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
})
</script>

<template>
  <div ref="root" class="relative inline-flex">
    <slot name="trigger" :open="open" :toggle="toggle">
      <button type="button" class="inline-flex items-center" :aria-expanded="open" aria-haspopup="true" @click="toggle">
        Menu
      </button>
    </slot>
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="open"
        :class="[
          'absolute top-full z-50 mt-2 min-w-48 rounded-lg border border-border-default bg-surface py-1 shadow-md dark:border-dark-border dark:bg-dark-surface',
          align === 'end' ? 'right-0' : 'left-0',
        ]"
        @click="close"
      >
        <slot name="content" :close="close" />
      </div>
    </Transition>
  </div>
</template>
