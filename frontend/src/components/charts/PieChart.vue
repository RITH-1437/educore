<script setup>
import IconButton from '../IconButton.vue'
import { PieChart as PieIcon, Table2 } from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  labels: { type: Array, required: true },
  values: { type: Array, required: true },
  label: { type: String, required: true },
  valueLabel: { type: String, default: 'Value' },
  suffix: { type: String, default: '' },
  donut: { type: Boolean, default: true },
  details: { type: Array, default: () => [] },
  // Optional slice colours (e.g. the brand chart series on the landing page); defaults to the palettes below.
  colors: { type: Array, default: null },
})

const canvas = ref(null)
const showTable = ref(false)
const dark = ref(false)
let chart = null
let observer = null

const PALETTE_LIGHT = [
  '#2563EB', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#64748B',
]
const PALETTE_DARK = [
  '#3B82F6', '#34D399', '#FBBF24', '#A78BFA', '#F472B6', '#22D3EE', '#FB923C', '#94A3B8',
]
const palette = () => props.colors ?? (dark.value ? PALETTE_DARK : PALETTE_LIGHT)

const total = computed(() => props.values.reduce((sum, v) => sum + (Number(v) || 0), 0))
const rows = computed(() =>
  props.labels.map((lbl, i) => {
    const val = Number(props.values[i]) || 0
    const pct = total.value > 0 ? ((val / total.value) * 100).toFixed(1) : '0.0'
    return {
      label: lbl,
      detail: props.details[i] || lbl,
      value: val,
      percentage: pct,
      color: palette()[i % palette().length],
    }
  }).filter((r) => r.value > 0 || props.labels.length <= 6)
)

function build() {
  if (chart) chart.destroy()
  if (!canvas.value) return

  const isDark = dark.value
  const colors = palette()
  const backgroundColors = props.labels.map((_, i) => colors[i % colors.length])
  const borderColor = isDark ? '#1E293B' : '#FFFFFF'

  chart = new Chart(canvas.value, {
    type: props.donut ? 'doughnut' : 'pie',
    data: {
      labels: props.labels,
      datasets: [
        {
          data: props.values,
          backgroundColor: backgroundColors,
          borderColor,
          borderWidth: 2,
          hoverOffset: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: props.donut ? '68%' : '0%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: isDark ? '#0F172A' : '#1E293B',
          titleColor: '#FFFFFF',
          bodyColor: '#F8FAFC',
          padding: 10,
          cornerRadius: 6,
          callbacks: {
            label(context) {
              const val = context.raw || 0
              const pct = total.value > 0 ? ((val / total.value) * 100).toFixed(1) : 0
              return ` ${val.toLocaleString()}${props.suffix} (${pct}%)`
            },
          },
        },
      },
    },
  })
}

onMounted(() => {
  dark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => {
    const isDark = document.documentElement.classList.contains('dark')
    if (isDark !== dark.value) {
      dark.value = isDark
      build()
    }
  })
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  build()
})

watch(() => [props.labels, props.values, props.donut], () => build(), { deep: true })
watch(showTable, (open) => {
  if (!open) setTimeout(build, 0)
})

onBeforeUnmount(() => {
  if (chart) chart.destroy()
  if (observer) observer.disconnect()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <span class="text-caption font-medium uppercase tracking-wider text-muted dark:text-dark-muted">
        Total: <strong class="text-ink dark:text-dark-ink">{{ total.toLocaleString() }}{{ suffix }}</strong>
      </span>
      <IconButton
        :icon="showTable ? PieIcon : Table2"
        size="sm"
        :label="showTable ? 'Show chart view' : 'Show table view'"
        @click="showTable = !showTable"
      />
    </div>

    <div v-show="!showTable" class="flex flex-col items-center gap-4 sm:flex-row sm:items-center sm:justify-around">
      <div class="relative h-56 w-56 flex-shrink-0">
        <canvas ref="canvas" :aria-label="label" role="img" />
        <div
          v-if="donut && total > 0"
          class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center"
        >
          <span class="text-h3 font-bold tabular-nums text-ink dark:text-dark-ink">{{ total.toLocaleString() }}</span>
          <span class="text-caption font-medium text-muted dark:text-dark-muted">{{ valueLabel }}</span>
        </div>
      </div>

      <div class="w-full flex-1 max-w-xs space-y-1.5 overflow-y-auto max-h-56 pr-1">
        <div
          v-for="item in rows"
          :key="item.label"
          class="flex items-center justify-between gap-2 rounded-md px-2 py-1 text-small transition-colors hover:bg-muted-light/50 dark:hover:bg-dark-muted/10"
        >
          <div class="flex items-center gap-2 min-w-0">
            <span class="size-2.5 flex-shrink-0 rounded-full" :style="{ backgroundColor: item.color }" />
            <span class="truncate text-ink dark:text-dark-ink" :title="item.detail">{{ item.label }}</span>
          </div>
          <div class="flex items-center gap-1.5 flex-shrink-0 tabular-nums">
            <span class="font-semibold text-ink dark:text-dark-ink">{{ item.value.toLocaleString() }}{{ suffix }}</span>
            <span class="text-caption text-muted dark:text-dark-muted">({{ item.percentage }}%)</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showTable" class="-mx-2 overflow-x-auto">
      <table class="min-w-full text-small">
        <caption class="sr-only">{{ label }}</caption>
        <thead>
          <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
            <th scope="col" class="px-2 py-2">Category</th>
            <th scope="col" class="px-2 py-2 text-right">{{ valueLabel }}</th>
            <th scope="col" class="px-2 py-2 text-right">Share</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-default dark:divide-dark-border">
          <tr v-for="item in rows" :key="item.label">
            <td class="px-2 py-2 text-ink dark:text-dark-ink">{{ item.detail }}</td>
            <td class="px-2 py-2 text-right tabular-nums text-ink dark:text-dark-ink">{{ item.value.toLocaleString() }}{{ suffix }}</td>
            <td class="px-2 py-2 text-right tabular-nums font-medium text-muted dark:text-dark-muted">{{ item.percentage }}%</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
