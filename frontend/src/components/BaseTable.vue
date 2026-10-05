<script setup>
import { router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { ChevronDown, ChevronUp } from '@lucide/vue'
import EmptyState from './EmptyState.vue'
import ErrorState from './ErrorState.vue'
import TableSkeleton from './TableSkeleton.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  loading: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  errorTitle: { type: String, default: 'Unable to load records' },
  sortable: { type: Boolean, default: false },
  rowClickable: { type: Boolean, default: false },
  // (row) => url. Makes every row open its record (UI-COMPONENTS §4) instead of a View/Eye action.
  rowHref: { type: Function, default: null },
  emptyTitle: { type: String, default: 'No records found' },
  emptyDescription: { type: String, default: 'There are no records to display yet.' },
  caption: { type: String, default: '' },
})

const emit = defineEmits(['sort', 'row-click', 'retry'])

// While the list is live-filtered (quiet visits, `useLiveFilters`), the columns
// keep the widths they had, so rows change without the table re-flowing, and
// "no results" keeps the header. A new page mounts a fresh table.
const table = ref(null)
const frozenWidths = ref(null)
let stopListening = null
onMounted(() => {
  stopListening = router.on('start', (event) => {
    if (event.detail.visit.showProgress !== false || frozenWidths.value || !table.value?.tHead) return
    frozenWidths.value = [...table.value.tHead.rows[0].cells].map((cell) => cell.getBoundingClientRect().width)
  })
})
onBeforeUnmount(() => stopListening?.())
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

const clickable = computed(() => props.rowClickable || Boolean(props.rowHref))

// Controls inside a row (buttons, links, inputs) keep their own behaviour, and
// selecting text in a row does not navigate.
const CONTROLS = 'a, button, input, select, textarea, label, [role="button"]'
const ignored = (event) => {
  const control = event.target.closest(CONTROLS)
  return (control && control !== event.currentTarget) || Boolean(window.getSelection()?.toString())
}

const open = (row, event) => {
  if (!clickable.value || ignored(event)) return
  if (!props.rowHref) return emit('row-click', row)
  const href = props.rowHref(row)
  // Ctrl/⌘-click and middle-click open a new tab, like a normal link.
  if (event.ctrlKey || event.metaKey || event.button === 1) window.open(href, '_blank', 'noopener')
  else router.visit(href)
}

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
  <div class="glass-card overflow-hidden rounded-xl border">
    <TableSkeleton v-if="loading" :columns="columns.length" />
    <div v-else-if="error" class="p-6 sm:p-10">
      <ErrorState :title="errorTitle" @retry="emit('retry')" />
    </div>
    <div v-else-if="rows.length === 0 && !frozenWidths" class="p-8 sm:p-12">
      <EmptyState :title="emptyTitle" :description="emptyDescription">
        <template v-if="$slots['empty-action']" #action><slot name="empty-action" /></template>
      </EmptyState>
    </div>
    <div v-else class="overflow-x-auto" tabindex="0" role="region" :aria-label="caption || 'Data table'">
      <table ref="table" class="min-w-full divide-y divide-border-default dark:divide-dark-border" :class="frozenWidths ? 'table-fixed' : ''">
        <caption v-if="caption" class="sr-only">{{ caption }}</caption>
        <colgroup v-if="frozenWidths">
          <col v-for="(width, index) in frozenWidths" :key="index" :style="{ width: `${width}px` }" />
        </colgroup>
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
          <tr v-if="!visibleRows.length">
            <td :colspan="columns.length" class="p-8 sm:p-12">
              <EmptyState :title="emptyTitle" :description="emptyDescription" />
            </td>
          </tr>
          <tr
            v-for="row in visibleRows"
            :key="row[rowKey]"
            class="transition-colors duration-150 hover:bg-background dark:hover:bg-dark-surface-2/50"
            :class="clickable ? 'cursor-pointer focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary' : ''"
            :tabindex="clickable ? 0 : undefined"
            @click="open(row, $event)"
            @auxclick="$event.button === 1 && open(row, $event)"
            @keydown.enter="open(row, $event)"
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
