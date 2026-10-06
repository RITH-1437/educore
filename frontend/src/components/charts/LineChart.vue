<script setup>
import IconButton from '../IconButton.vue'
import { ChartLine, Table2 } from '@lucide/vue'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'

// Single-series line chart for change over time (UI-COMPONENTS §10, dataviz
// skill): one hue from the chart token, a 2px line with 8px markers, one value
// axis, a gap where there is nothing to measure (null is never drawn as 0),
// a hover tooltip across the whole column, and a "Show as table" alternative.
// Same tokens and theme observer as BarChart.
const props = defineProps({
  labels: { type: Array, required: true },
  values: { type: Array, required: true },
  label: { type: String, required: true }, // accessible name + table caption
  valueLabel: { type: String, default: 'Value' },
  suffix: { type: String, default: '' },
  max: { type: Number, default: null },
  decimals: { type: Number, default: null },
})

const canvas = ref(null)
const showTable = ref(false)
const dark = ref(false)
let chart = null
let observer = null

const FALLBACK = {
  '--color-chart-primary': '#2563EB', '--color-dark-chart-primary': '#3B82F6',
  '--color-ink': '#1E293B', '--color-dark-ink': '#F8FAFC',
  '--color-muted': '#64748B', '--color-dark-muted': '#94A3B8',
  '--color-border-default': '#E2E8F0', '--color-dark-border': '#334155',
  '--color-surface': '#FFFFFF', '--color-dark-surface': '#111827',
}
const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || FALLBACK[name]
const format = (v) => {
  if (v === null || v === undefined) return '—'
  const n = Number(v)
  return `${props.decimals === null ? n.toLocaleString() : n.toFixed(props.decimals)}${props.suffix}`
}

function build() {
  if (chart) chart.destroy()
  if (!canvas.value) return

  const isDark = dark.value
  const line = css(isDark ? '--color-dark-chart-primary' : '--color-chart-primary')
  const ink = css(isDark ? '--color-dark-ink' : '--color-ink')
  const muted = css(isDark ? '--color-dark-muted' : '--color-muted')
  const grid = css(isDark ? '--color-dark-border' : '--color-border-default')
  const surface = css(isDark ? '--color-dark-surface' : '--color-surface')

  chart = new Chart(canvas.value, {
    type: 'line',
    data: {
      labels: props.labels,
      datasets: [{
        label: props.valueLabel,
        data: props.values,
        borderColor: line,
        backgroundColor: line,
        borderWidth: 2,
        pointRadius: 4, // 8px marker
        pointHoverRadius: 6,
        pointBorderColor: surface, // 2px surface ring keeps markers legible over the line
        pointBorderWidth: 2,
        tension: 0,
        spanGaps: false,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? false : { duration: 300 },
      interaction: { mode: 'index', intersect: false }, // hover anywhere in the column
      plugins: {
        legend: { display: false }, // single series: the card title names it
        tooltip: {
          backgroundColor: surface,
          titleColor: ink,
          bodyColor: ink,
          borderColor: grid,
          borderWidth: 1,
          padding: 10,
          displayColors: false,
          callbacks: { label: (item) => `${props.valueLabel}: ${format(item.raw)}` },
        },
      },
      scales: {
        // Inset the first and last points so their markers and labels are not clipped.
        x: { offset: true, grid: { display: false }, border: { color: grid }, ticks: { color: muted, font: { size: 12 } } },
        y: { beginAtZero: true, max: props.max ?? undefined, grid: { color: grid }, border: { display: false }, ticks: { color: muted, font: { size: 12 } } },
      },
    },
  })
}

onMounted(() => {
  dark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => { dark.value = document.documentElement.classList.contains('dark') })
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  build()
})
watch([() => props.labels, () => props.values, () => props.max, dark], build)
onBeforeUnmount(() => {
  observer?.disconnect()
  chart?.destroy()
})
</script>

<template>
  <div>
    <div v-show="!showTable" class="relative h-64">
      <canvas ref="canvas" role="img" :aria-label="`${label}: ${labels.map((l, i) => `${l} ${format(values[i])}`).join(', ')}`" />
    </div>
    <div v-if="showTable" class="-mx-2 overflow-x-auto">
      <table class="min-w-full text-small">
        <caption class="sr-only">{{ label }}</caption>
        <tbody class="divide-y divide-border-default dark:divide-dark-border">
          <tr v-for="(l, i) in labels" :key="l">
            <th scope="row" class="px-2 py-1.5 text-left font-normal text-ink dark:text-dark-ink">{{ l }}</th>
            <td class="px-2 py-1.5 text-right tabular-nums text-ink dark:text-dark-ink">{{ format(values[i]) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-2 flex justify-end">
      <IconButton :icon="showTable ? ChartLine : Table2" :label="showTable ? 'Show as chart' : 'Show as table'" @click="showTable = !showTable" />
    </div>
  </div>
</template>
