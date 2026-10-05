<script setup>
import { computed } from 'vue'
import BaseTooltip from '../BaseTooltip.vue'
import { bandTone, scaleRanges } from '../../utils/grades'

// The grading scale as one 0–100% bar: a segment per band, sized by its range,
// shaded by grade points (failing bands in the error tint), lettered, with the
// boundaries underneath. Hover or focus a segment for its exact numbers; the
// table on the page carries the same data for screen readers.
const props = defineProps({
  /** `[{ grade, min_percentage, grade_point, is_pass }]` in any order (form rows or saved bands). */
  bands: { type: Array, required: true },
})

const ascending = computed(() => [...scaleRanges(props.bands)].reverse())
const maxPoints = computed(() => Math.max(0, ...ascending.value.map((band) => band.points)))
const segments = computed(() => ascending.value.map((band, i) => ({
  ...band,
  width: (ascending.value[i + 1]?.min ?? 100) - band.min,
})))
const passMark = computed(() => ascending.value.find((band) => band.is_pass)?.min ?? null)
// Boundary labels, thinned so they never collide: a label shows when it is far
// enough from the previous shown one (and from 100%) — about 10% of the bar on
// phones, 3% on wider screens. The pass mark always shows.
const thin = (gap) => {
  let last = -Infinity
  return segments.value.map((band) => {
    const keep = band.min === passMark.value || (band.min - last >= gap && 100 - band.min >= gap * 0.8)
    if (keep) last = band.min
    return keep
  })
}
const ticks = computed(() => {
  const wide = thin(3)
  const narrow = thin(10)
  return segments.value.map((band, i) => ({ min: band.min, key: band.index, show: wide[i] ? (narrow[i] ? '' : 'max-sm:hidden') : 'hidden' }))
})
const hasFail = computed(() => ascending.value.some((band) => !band.is_pass))
const fmt = (value) => `${Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 })}%`
const describe = (band) => `${band.grade || '—'}: ${fmt(band.min)} – ${fmt(band.max)} · ${band.points.toFixed(2)} grade points · ${band.is_pass ? 'Pass' : 'Fail'}`
</script>

<template>
  <figure class="space-y-2">
    <p v-if="!segments.length" class="rounded-lg border border-dashed border-border-muted p-6 text-center text-small text-muted dark:border-dark-border dark:text-dark-muted">
      Give each band a starting percentage to see the scale.
    </p>
    <template v-else>
      <!-- No overflow clipping (tooltips must escape): the end segments round themselves. -->
      <ul class="flex h-14 gap-0.5" aria-label="Grade bands from 0% to 100%">
        <li v-for="(band, i) in segments" :key="band.index" class="flex min-w-0" :style="{ flexGrow: band.width, flexBasis: 0 }">
          <BaseTooltip :content="describe(band)" class="h-full w-full">
            <span
              tabindex="0"
              class="flex h-full w-full items-center justify-center text-small font-semibold transition-[filter] duration-150 hover:brightness-95 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
              :class="[bandTone(band, maxPoints), i === 0 ? 'rounded-l-lg' : '', i === segments.length - 1 ? 'rounded-r-lg' : '']"
              :aria-label="describe(band)"
            >{{ band.width >= 3 ? band.grade : '' }}</span>
          </BaseTooltip>
        </li>
      </ul>

      <!-- Boundaries: where each band starts, and 100% at the end. -->
      <div class="relative h-5 text-caption tabular-nums text-muted dark:text-dark-muted" aria-hidden="true">
        <span v-for="tick in ticks" :key="tick.key" class="absolute -translate-x-1/2 first:translate-x-0" :class="[tick.show, tick.min === passMark && hasFail ? 'font-semibold text-success dark:text-green-300' : '']" :style="{ left: `${tick.min}%` }">{{ fmt(tick.min) }}</span>
        <span class="absolute right-0">100%</span>
      </div>

      <figcaption class="flex flex-wrap items-center gap-x-4 gap-y-1 text-caption text-muted dark:text-dark-muted">
        <span class="inline-flex items-center gap-1.5"><span class="flex gap-0.5" aria-hidden="true"><span class="h-3 w-3 rounded-sm bg-primary/15 dark:bg-dark-primary/20" /><span class="h-3 w-3 rounded-sm bg-primary/45 dark:bg-dark-primary/50" /><span class="h-3 w-3 rounded-sm bg-primary dark:bg-dark-primary" /></span>Darker = more grade points</span>
        <span v-if="hasFail" class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-error/15 dark:bg-error/20" aria-hidden="true" />Fail</span>
        <span v-if="passMark !== null && hasFail">Pass mark <strong class="text-success dark:text-green-300">{{ fmt(passMark) }}</strong></span>
      </figcaption>
    </template>
  </figure>
</template>
