<script setup>
import { computed, ref } from 'vue'
import { ChevronDown, ChevronUp } from '@lucide/vue'
import EmptyState from './EmptyState.vue'
import LoadingSpinner from './LoadingSpinner.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  loading: { type: Boolean, default: false },
  sortable: { type: Boolean, default: false },
  rowClickable: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'No records found' },
  emptyDescription: { type: String, default: 'There are no records to display yet.' },
  caption: { type: String, default: '' },
})

const emit = defineEmits(['sort', 'row-click'])
const sortKey = ref('')
const sortDirection = ref('asc')

const visibleRows = computed(() => {
  if (!props.sortable || !sortKey.value) return props.rows
  return [...props.rows].sort((left, right) => {
    const a = left[sortKey.value] ?? ''
    const b = right[sortKey.value] ?? ''
    const order = String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' })
    return sortDirection.value === 'asc' ? order : -order
  })
})

const sort = (column) => {
  if (!props.sortable || column.sortable === false) return
  if (sortKey.value === column.key) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  else {
    sortKey.value = column.key
    sortDirection.value = 'asc'
  }
  emit('sort', { sort_by: sortKey.value, sort_dir: sortDirection.value })
}
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-border-default bg-surface shadow-sm dark:border-dark-border dark:bg-dark-surface">
    <div v-if="loading" class="flex min-h-48 items-center justify-center" role="status" aria-label="Loading table">
      <LoadingSpinner label="Loading records" />
    </div>
    <div v-else-if="rows.length === 0" class="p-8 sm:p-12">
      <EmptyState :title="emptyTitle" :description="emptyDescription">
        <template v-if="$slots['empty-action']" #action><slot name="empty-action" /></template>
      </EmptyState>
    </div>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-border-default dark:divide-dark-border">
        <caption v-if="caption" class="sr-only">{{ caption }}</caption>
        <thead class="bg-background/70 dark:bg-dark-surface-2/50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              :class="[
                'whitespace-nowrap px-4 py-3 text-left text-small font-semibold text-muted dark:text-dark-muted',
                column.align === 'right' ? 'text-right' : '',
                column.align === 'center' ? 'text-center' : '',
              ]"
            >
              <button
                v-if="sortable && column.sortable !== false && column.key"
                type="button"
                class="inline-flex items-center gap-1 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                @click="sort(column)"
              >
                {{ column.label }}
                <ChevronUp v-if="sortKey === column.key && sortDirection === 'asc'" class="h-4 w-4" aria-hidden="true" />
                <ChevronDown v-else-if="sortKey === column.key" class="h-4 w-4" aria-hidden="true" />
              </button>
              <span v-else>{{ column.label }}</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-default dark:divide-dark-border">
          <tr
            v-for="row in visibleRows"
            :key="row[rowKey]"
            class="transition-colors hover:bg-background dark:hover:bg-dark-surface-2/50"
            @click="rowClickable && emit('row-click', row)"
            :class="rowClickable ? 'cursor-pointer' : ''"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-4 py-3 text-small text-ink dark:text-dark-ink',
                column.align === 'right' ? 'text-right' : '',
                column.align === 'center' ? 'text-center' : '',
              ]"
            >
              <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]" :column="column">
                {{ row[column.key] ?? '—' }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <footer v-if="$slots.footer" class="border-t border-border-default px-4 py-3 dark:border-dark-border">
      <slot name="footer" />
    </footer>
  </div>
</template>
