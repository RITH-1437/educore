<script setup>
import { ArrowRight, List, PieChart as PieIcon } from '@lucide/vue'
import { computed, ref } from 'vue'
import BaseCard from '../BaseCard.vue'
import EmptyState from '../EmptyState.vue'
import IconButton from '../IconButton.vue'
import StatusBadge from '../StatusBadge.vue'
import PieChart from '../charts/PieChart.vue'

// One workload area on the Analytics page (document requests, internships,
// invoices): its records by status, as a donut or as the full list of rows.
const props = defineProps({
  title: { type: String, required: true },
  /** `[{ status, total }]` in the workflow's order — every status, zeros included. */
  rows: { type: Array, required: true },
  href: { type: String, required: true },
  valueLabel: { type: String, required: true },
})

const label = (status) => status.replaceAll('_', ' ').replace(/^./, (c) => c.toUpperCase())
const total = computed(() => props.rows.reduce((sum, row) => sum + row.total, 0))
// Only statuses with records become slices; each keeps the colour of its place in
// the workflow, so it does not change when another status empties.
const slices = computed(() => props.rows.map((row, slot) => ({ ...row, slot })).filter((row) => row.total > 0))

// A donut reads at a glance up to about six slices; beyond that the rows are clearer.
const view = ref(slices.value.length > 6 ? 'rows' : 'chart')
const noun = computed(() => props.title.toLowerCase())
</script>

<template>
  <BaseCard :title="title">
    <template #actions>
      <div class="flex items-center gap-1">
        <IconButton
          :icon="view === 'chart' ? List : PieIcon"
          :label="view === 'chart' ? `Show ${noun} as rows` : `Show ${noun} as a donut chart`"
          @click="view = view === 'chart' ? 'rows' : 'chart'"
        />
        <IconButton :icon="ArrowRight" :href="href" :label="`Open ${noun}`" />
      </div>
    </template>

    <template v-if="view === 'chart'">
      <EmptyState v-if="!total" :title="`No ${noun} yet`" description="The chart fills in as records arrive; the rows view lists every status." />
      <PieChart
        v-else
        :label="`${title} by status`"
        :value-label="valueLabel"
        :labels="slices.map((row) => label(row.status))"
        :values="slices.map((row) => row.total)"
        :slots="slices.map((row) => row.slot)"
        :table-toggle="false"
        legend-below
      />
    </template>

    <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
      <li v-for="row in rows" :key="row.status" class="flex items-center justify-between py-2">
        <StatusBadge :status="row.status" :label="label(row.status)" />
        <span class="text-small font-semibold tabular-nums text-ink dark:text-dark-ink">
          {{ row.total }}<span v-if="total" class="ml-2 text-caption font-normal text-muted dark:text-dark-muted">{{ ((row.total / total) * 100).toFixed(1) }}%</span>
        </span>
      </li>
    </ul>
  </BaseCard>
</template>
