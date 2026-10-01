<script setup>
import { onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { X } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md', validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value) },
  closeable: { type: Boolean, default: true },
  closeOnOverlayClick: { type: Boolean, default: true },
  closeOnEscape: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'close'])
const modalRef = ref(null)
const titleId = `modal-title-${useId()}`
let previousFocus = null
let previousOverflow = ''

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-3xl',
  xl: 'max-w-5xl',
}

const close = () => {
  if (!props.closeable) return
  emit('update:modelValue', false)
  emit('close')
}

const onOverlayClick = (event) => {
  if (event.target === event.currentTarget && props.closeOnOverlayClick) close()
}

const onKeydown = (event) => {
  if (!props.modelValue) return
  if (event.key === 'Escape' && props.closeOnEscape) {
    close()
    return
  }
  if (event.key !== 'Tab') return

  const focusable = modalRef.value?.querySelectorAll(
    'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
  )
  if (!focusable?.length) return

  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (typeof document === 'undefined') return
    if (open) {
      previousFocus = document.activeElement
      previousOverflow = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      document.addEventListener('keydown', onKeydown)
      requestAnimationFrame(() => {
        modalRef.value?.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')?.focus()
      })
    } else {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', onKeydown)
      previousFocus?.focus?.()
    }
  },
  { immediate: true },
)

onMounted(() => {
  if (props.modelValue) document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = previousOverflow
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[60] flex items-end justify-center p-4 sm:items-center sm:p-6"
      role="presentation"
      @click="onOverlayClick"
    >
      <div class="absolute inset-0 bg-primary-dark/50 backdrop-blur-sm motion-safe:animate-fade-in" aria-hidden="true" />

      <section
        ref="modalRef"
        :class="[
          'glass-panel relative z-[1] flex max-h-[min(90dvh,48rem)] w-full flex-col overflow-hidden rounded-xl border motion-safe:animate-scale-in',
          sizeClasses[size],
        ]"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="title ? titleId : undefined"
        tabindex="-1"
      >
        <header v-if="title || closeable || $slots.header" class="flex items-center justify-between gap-4 border-b border-border-default px-6 py-4 dark:border-dark-border">
          <slot name="header">
            <h2 v-if="title" :id="titleId" class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ title }}</h2>
          </slot>
          <button
            v-if="closeable"
            type="button"
            class="rounded-md p-2 text-muted transition-colors hover:bg-background hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-surface-2 dark:hover:text-dark-ink"
            aria-label="Close dialog"
            @click="close"
          >
            <X class="h-5 w-5" aria-hidden="true" />
          </button>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto p-6">
          <slot />
        </div>
        <footer v-if="$slots.footer" class="border-t border-border-default px-6 py-4 dark:border-dark-border">
          <slot name="footer" />
        </footer>
      </section>
    </div>
  </Teleport>
</template>
