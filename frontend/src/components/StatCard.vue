<script setup>
import { computed } from 'vue'
import { ArrowRight } from '@lucide/vue'
import BaseBadge from './BaseBadge.vue'
import BaseCard from './BaseCard.vue'
import { useCountUp } from '../composables/useCountUp'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: null },
  detail: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  href: { type: String, default: '' },
  tone: { type: String, default: 'primary', validator: (value) => ['primary', 'secondary', 'success', 'warning', 'muted'].includes(value) },
  /** Shown instead of the link hint when the module is not available yet. */
  unavailable: { type: String, default: '' },
})

const toneClasses = {
  primary: 'bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary',
  secondary: 'bg-secondary/15 text-primary-dark dark:text-secondary',
  success: 'bg-success/10 text-success dark:bg-success/15 dark:text-green-300',
  warning: 'bg-warning/15 text-primary-dark dark:text-amber-200',
  muted: 'bg-muted/10 text-muted dark:bg-dark-surface-2 dark:text-dark-muted',
}

const numeric = computed(() => (typeof props.value === 'number' ? props.value : null))
const counted = useCountUp(numeric)
const shown = computed(() => (numeric.value === null ? (props.value ?? '—') : counted.value.toLocaleString()))
</script>

<template>
  <BaseCard :href="href || undefined" :hoverable="Boolean(href)" class="group">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <p class="truncate text-small font-medium text-muted dark:text-dark-muted">{{ label }}</p>
        <p class="mt-2 text-h3 font-semibold tabular-nums text-ink dark:text-dark-ink">{{ shown }}</p>
        <p v-if="detail" class="mt-1 truncate text-caption text-muted dark:text-dark-muted">{{ detail }}</p>
      </div>
      <span v-if="icon" :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition-transform duration-200 ease-out group-hover:scale-105 motion-reduce:transition-none', toneClasses[tone]]">
        <component :is="icon" class="h-5 w-5" aria-hidden="true" />
      </span>
    </div>
    <span v-if="href" class="mt-4 inline-flex items-center gap-1 text-caption font-semibold text-primary dark:text-dark-primary">
      Open <ArrowRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5 motion-reduce:transition-none" aria-hidden="true" />
    </span>
    <BaseBadge v-else-if="unavailable" class="mt-4" variant="muted" size="sm">{{ unavailable }}</BaseBadge>
  </BaseCard>
</template>
