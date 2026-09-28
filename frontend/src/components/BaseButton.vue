<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  type: { type: String, default: 'button' },
  href: { type: String, default: '' },
  variant: { type: String, default: 'primary', validator: (value) => ['primary', 'secondary', 'outline', 'ghost', 'danger', 'success'].includes(value) },
  size: { type: String, default: 'md', validator: (value) => ['sm', 'md', 'lg'].includes(value) },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

defineOptions({ inheritAttrs: false })

const variants = {
  primary: 'border border-primary bg-primary text-white hover:bg-primary-dark hover:border-primary-dark focus-visible:outline-primary active:scale-[0.98]',
  secondary: 'border border-border-default bg-surface text-ink hover:bg-background focus-visible:outline-primary active:scale-[0.98] dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2',
  outline: 'border border-primary bg-transparent text-primary hover:bg-primary/5 focus-visible:outline-primary active:scale-[0.98] dark:text-dark-primary dark:border-dark-primary',
  ghost: 'border border-transparent bg-transparent text-ink hover:bg-background focus-visible:outline-primary active:scale-[0.98] dark:text-dark-ink dark:hover:bg-dark-surface-2',
  danger: 'border border-error bg-error text-white hover:bg-error/90 focus-visible:outline-error active:scale-[0.98]',
  success: 'border border-success bg-success text-white hover:bg-success/90 focus-visible:outline-success active:scale-[0.98]',
}

const sizes = {
  sm: 'min-h-8 gap-2 rounded-md px-3 py-1.5 text-caption',
  md: 'min-h-10 gap-2 rounded-md px-4 py-2 text-button',
  lg: 'min-h-12 gap-2 rounded-md px-5 py-3 text-button',
}

const classes = computed(() => [
  'inline-flex items-center justify-center font-medium transition duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none',
  variants[props.variant],
  sizes[props.size],
])
</script>

<template>
  <Link
    v-if="href && !disabled && !loading"
    :href="href"
    :class="classes"
    v-bind="$attrs"
  >
    <slot />
  </Link>
  <button
    v-else
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    :class="classes"
    v-bind="$attrs"
  >
    <span v-if="loading" class="h-4 w-4 animate-spin rounded-pill border-2 border-current/30 border-t-current motion-reduce:animate-none" aria-hidden="true" />
    <slot />
  </button>
</template>
