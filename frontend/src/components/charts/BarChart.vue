<script setup>
import IconButton from '../IconButton.vue'
import { ChartColumn, Table2 } from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'

// Single-series bar chart (UI-COMPONENTS §10, dataviz skill): one hue from the
// chart token, thin bars with a 4px rounded data end, a surface gap between
// bars, recessive grid, hover tooltip, and a "Show as table" alternative so
// the numbers never depend on the canvas. Theme-aware via the <html> `dark`
// class (observed, not toggled).
const props = defineProps({
  labels: { type: Array, required: true },
  values: { type: Array, required: true },
  label: { type: String, required: true }, // accessible name + table caption
  valueLabel: { type: String, default: 'Value' },
  suffix: { type: String, default: '' },
  horizontal: { type: Boolean, default: false },
  max: { type: Number, default: null },
  // Optional long names shown in the tooltip / table (labels stay short on the axis).
  details: { type: Array, default: () => [] },
})

const canvas = ref(null)
const showTable = ref(false)
const dark = ref(false)
let chart = null
let observer = null

// Tailwind may omit theme variables no utility uses, so each token has its
// documented value (DESIGN-TOKENS / UI-COMPONENTS §10) as a fallback.
const FALLBACK = {
  '--color-chart-primary': '#2563EB', '--color-dark-chart-primary': '#3B82F6',
  '--color-ink': '#1E293B', '--color-dark-ink': '#F8FAFC',
  '--color-muted': '#64748B', '--color-dark-muted': '#94A3B8',
  '--color-border-default': '#E2E8F0', '--color-dark-border': '#334155',
  '--color-surface': '#FFFFFF', '--color-dark-surface': '#111827',
}
const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || FALLBACK[name]
const format = (v) => (v === null || v === undefined ? '—' : `${Number(v).toLocaleString()}${props.suffix}`)
const height = computed(() => (props.horizontal ? Math.max(160, props.labels.length * 36 + 40) : 260))

function build() {
  if (chart) chart.destroy()
  if (!canvas.value) return

  const isDark = dark.value
  const bar = css(isDark ? '--color-dark-chart-primary' : '--color-chart-primary')
  const ink = css(isDark ? '--color-dark-ink' : '--color-ink')
  const muted = css(isDark ? '--color-dark-muted' : '--color-muted')
  const grid = css(isDark ? '--color-dark-border' : '--color-border-default')
  const surface = css(isDark ? '--color-dark-surface' : '--color-surface')
  const valueAxis = { beginAtZero: true, max: props.max ?? undefined, grid: { color: grid }, border: { display: false }, ticks: { color: muted, font: { size: 12 }, precision: 0 } }
  const categoryAxis = { grid: { display: false }, border: { color: grid }, ticks: { color: muted, font: { size: 12 } } }

  chart = new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: [{
        label: props.valueLabel,
        data: props.values,
        backgroundColor: bar,
        hoverBackgroundColor: bar,
        borderRadius: 4,
        borderSkipped: 'start', // rounded data end only; flat on the baseline
        maxBarThickness: 28,
        categoryPercentage: 0.8,
        barPercentage: 0.9,
      }],
    },
    options: {
      indexAxis: props.horizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: false,
      animation: window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? false : { duration: 300 },
      interaction: { mode: 'index', intersect: false }, // hit target = the whole band, not just the bar
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
          callbacks: {
            title: (items) => props.details[items[0].dataIndex] ?? items[0].label,
            label: (item) => `${props.valueLabel}: ${format(item.raw)}`,
          },
        },
      },
      scales: props.horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis },
    },
  })
}

onMounted(() => {
  dark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => { dark.value = document.documentElement.classList.contains('dark') })
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  build()
})
watch([() => props.labels, () => props.values, dark], build)
onBeforeUnmount(() => {
  observer?.disconnect()
  chart?.destroy()
})
</script>

<template>
  <div>
    <div v-show="!showTable" :style="{ height: `${height}px` }" class="relative">
      <canvas ref="canvas" role="img" :aria-label="`${label}: ${labels.map((l, i) => `${l} ${format(values[i])}`).join(', ')}`" />
    </div>
    <div v-if="showTable" class="-mx-2 overflow-x-auto">
      <table class="min-w-full text-small">
        <caption class="sr-only">{{ label }}</caption>
        <tbody class="divide-y divide-border-default dark:divide-dark-border">
          <tr v-for="(l, i) in labels" :key="l">
            <th scope="row" class="px-2 py-1.5 text-left font-normal text-ink dark:text-dark-ink">{{ details[i] ?? l }}</th>
            <td class="px-2 py-1.5 text-right tabular-nums text-ink dark:text-dark-ink">{{ format(values[i]) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-2 flex justify-end">
      <IconButton :icon="showTable ? ChartColumn : Table2" :label="showTable ? 'Show as chart' : 'Show as table'" @click="showTable = !showTable" />
    </div>
  </div>
</template>
