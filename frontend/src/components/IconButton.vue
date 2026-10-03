<script setup>
import { Link } from '@inertiajs/vue3'
import { LoaderCircle } from '@lucide/vue'
import { computed } from 'vue'
import BaseTooltip from './BaseTooltip.vue'

// Icon-only action (UI-COMPONENTS §2 "Icon buttons"): EduCore shows actions as
// icons wherever an icon reads clearly. The label is required: it is the
// accessible name and the tooltip, so the action never depends on
// recognising the icon.
const props = defineProps({
  icon: { type: [Object, Function], required: true },
  label: { type: String, required: true },
  /** Navigates with Inertia; with `native`, renders a plain <a> (file downloads, CSV exports). */
  href: { type: String, default: '' },
  native: { type: Boolean, default: false },
  /**
   * default — muted, primary on hover (most actions) · primary — filled, the
   * page's main create action · success — approve / complete / restore ·
   * danger — destructive (delete, reject, revoke, drop).
   */
  variant: { type: String, default: 'default', validator: (value) => ['default', 'primary', 'success', 'danger'].includes(value) },
  /** sm (32px) in rows, cards and forms · md (40px) in page headers and filter bars. */
  size: { type: String, default: 'sm', validator: (value) => ['sm', 'md'].includes(value) },
  type: { type: String, default: 'button', validator: (value) => ['button', 'submit'].includes(value) },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  /** For view toggles: the pressed state (left out when the button is not a toggle). */
  pressed: { type: Boolean, default: undefined },
})

defineEmits(['click'])

const variants = {
  default: 'text-muted hover:bg-primary/10 hover:text-primary focus-visible:outline-primary dark:text-dark-muted dark:hover:bg-dark-primary/15 dark:hover:text-dark-primary',
  primary: 'bg-primary text-white shadow-sm hover:bg-primary-dark focus-visible:outline-primary dark:bg-dark-primary dark:text-dark-bg dark:hover:bg-dark-primary/90',
  success: 'text-muted hover:bg-success/10 hover:text-success focus-visible:outline-success dark:text-dark-muted dark:hover:bg-success/20 dark:hover:text-green-300',
  danger: 'text-muted hover:bg-error/10 hover:text-error focus-visible:outline-error dark:text-dark-muted dark:hover:bg-error/20 dark:hover:text-red-400',
}

const classes = computed(() => [
  'inline-flex shrink-0 items-center justify-center rounded-md transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-40 motion-reduce:transition-none',
  props.size === 'md' ? 'h-10 w-10' : 'h-8 w-8',
  variants[props.variant],
])
const iconClass = computed(() => (props.size === 'md' ? 'h-5 w-5' : 'h-4 w-4'))
</script>

<template>
  <BaseTooltip :content="label">
    <a v-if="href && native && !disabled" :href="href" :class="classes" :aria-label="label">
      <component :is="icon" :class="iconClass" aria-hidden="true" />
    </a>
    <Link v-else-if="href && !disabled" :href="href" :class="classes" :aria-label="label">
      <component :is="icon" :class="iconClass" aria-hidden="true" />
    </Link>
    <button
      v-else
      :type="type"
      :class="classes"
      :aria-label="label"
      :aria-pressed="pressed"
      :aria-busy="loading || undefined"
      :disabled="disabled || loading"
      @click="$emit('click', $event)"
    >
      <LoaderCircle v-if="loading" :class="[iconClass, 'animate-spin motion-reduce:animate-none']" aria-hidden="true" />
      <component :is="icon" v-else :class="iconClass" aria-hidden="true" />
    </button>
  </BaseTooltip>
</template>
