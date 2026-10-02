<script setup>
import { Link } from '@inertiajs/vue3'
import BaseTooltip from './BaseTooltip.vue'

// Icon-only action for table rows and cards (UI-COMPONENTS: "Actions column:
// rightmost, icon buttons"). The label is required: it is the accessible name
// and the tooltip, so the action never depends on recognising the icon.
// Same look as the original Users actions: muted at rest, primary on hover,
// error for destructive actions.
const props = defineProps({
  icon: { type: [Object, Function], required: true },
  label: { type: String, required: true },
  href: { type: String, default: '' },
  variant: { type: String, default: 'default', validator: (value) => ['default', 'danger'].includes(value) },
  disabled: { type: Boolean, default: false },
})

defineEmits(['click'])

const classes = [
  'inline-flex h-8 w-8 items-center justify-center rounded-md text-muted transition-colors focus-visible:outline-2 disabled:pointer-events-none disabled:opacity-40 dark:text-dark-muted',
  props.variant === 'danger'
    ? 'hover:bg-error/10 hover:text-error focus-visible:outline-error dark:hover:bg-error/20 dark:hover:text-red-400'
    : 'hover:bg-primary/10 hover:text-primary focus-visible:outline-primary dark:hover:bg-dark-primary/15 dark:hover:text-dark-primary',
]
</script>

<template>
  <BaseTooltip :content="label">
    <Link v-if="href && !disabled" :href="href" :class="classes" :aria-label="label">
      <component :is="icon" class="h-4 w-4" aria-hidden="true" />
    </Link>
    <button v-else type="button" :class="classes" :aria-label="label" :disabled="disabled" @click="$emit('click', $event)">
      <component :is="icon" class="h-4 w-4" aria-hidden="true" />
    </button>
  </BaseTooltip>
</template>
