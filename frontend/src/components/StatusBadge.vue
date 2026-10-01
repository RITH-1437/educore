<script setup>
import { computed } from 'vue'
import BaseBadge from './BaseBadge.vue'

const props = defineProps({
  status: { type: String, required: true },
  size: { type: String, default: 'sm', validator: (value) => ['sm', 'md'].includes(value) },
  dot: { type: Boolean, default: true },
  label: { type: String, default: '' },
})

const statusMap = {
  planned: { variant: 'muted', label: 'Planned' },
  active: { variant: 'success', label: 'Active' },
  completed: { variant: 'success', label: 'Completed' },
  open: { variant: 'success', label: 'Open' },
  closed: { variant: 'warning', label: 'Closed' },
  pending: { variant: 'warning', label: 'Pending' },
  inactive: { variant: 'muted', label: 'Inactive' },
  archived: { variant: 'warning', label: 'Archived' },
  draft: { variant: 'muted', label: 'Draft' },
  approved: { variant: 'success', label: 'Approved' },
  published: { variant: 'success', label: 'Published' },
  rejected: { variant: 'error', label: 'Rejected' },
  cancelled: { variant: 'muted', label: 'Cancelled' },
  failed: { variant: 'error', label: 'Failed' },
}

const mappedStatus = computed(() => statusMap[props.status.toLowerCase()] ?? {
  variant: 'muted',
  label: props.status.replaceAll('-', ' '),
})
</script>

<template>
  <BaseBadge :variant="mappedStatus.variant" :size="size" :dot="dot">
    {{ label || mappedStatus.label }}
  </BaseBadge>
</template>
