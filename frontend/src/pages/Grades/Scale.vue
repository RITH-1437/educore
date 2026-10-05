<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import ScaleBar from '../../components/grades/ScaleBar.vue'
import ScaleEditor from '../../components/grades/ScaleEditor.vue'
import { bandTone, scaleRanges } from '../../utils/grades'

const props = defineProps({
  scale: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
})

// Each band only states where it starts; the server derives where it ends.
const form = useForm({
  bands: props.scale.data.map((band) => ({ grade: band.grade, min_percentage: band.min_percentage, grade_point: band.grade_point, is_pass: band.is_pass })),
})
const save = () => form.put('/grading-scale', { preserveScroll: true, onSuccess: () => form.defaults() })

// Saved scale, top grade first (as the server returns it).
const saved = computed(() => scaleRanges(props.scale.data))
const maxPoints = computed(() => Math.max(0, ...saved.value.map((band) => band.points)))
const top = computed(() => saved.value[0] ?? null)
const lowestPass = computed(() => [...saved.value].reverse().find((band) => band.is_pass) ?? null)
const pct = (value) => `${Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 })}%`

const metrics = computed(() => [
  { label: 'Grades', value: saved.value.length, detail: `${saved.value.filter((band) => band.is_pass).length} passing, ${saved.value.filter((band) => !band.is_pass).length} failing` },
  { label: 'Pass mark', value: lowestPass.value ? pct(lowestPass.value.min) : '—', detail: lowestPass.value ? `Lowest pass: ${lowestPass.value.grade}` : 'No passing grade', tone: 'success' },
  { label: 'Top grade', value: top.value?.grade ?? '—', detail: top.value ? `From ${pct(top.value.min)} · ${top.value.points.toFixed(2)} points` : '', tone: 'secondary' },
  { label: 'Grade points', value: `0 – ${maxPoints.value.toFixed(2)}`, detail: 'Used for GPA', tone: 'muted' },
])
</script>

<template>
  <Head title="Grading scale - EduCore" />
  <div class="mx-auto max-w-5xl space-y-6">
    <PageHeader eyebrow="Grades & GPA" title="Grading scale" :description="`The “${scale.name}” scale turns a course total (0–100%) into a letter grade and the grade points used for GPA.`" />

    <section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Scale at a glance">
      <StatCard v-for="metric in metrics" :key="metric.label" v-bind="metric" />
    </section>

    <BaseCard padding="lg" title="Scale">
      <template #description>{{ canEdit ? 'Updates as you edit the bands below.' : 'Hover or focus a band for its exact range.' }}</template>
      <template v-if="canEdit && form.isDirty" #actions><BaseBadge variant="warning">Preview — not saved</BaseBadge></template>
      <ScaleBar :bands="canEdit ? form.bands : scale.data" />
    </BaseCard>

    <BaseCard v-if="canEdit" padding="lg" title="Bands">
      <template #description>Give each grade the percentage it starts at; the lowest starts at 0%, and a higher band needs at least as many grade points. Approved grades keep their letters; drafts use the new scale when recomputed.</template>
      <ScaleEditor :form="form" @save="save" />
    </BaseCard>

    <BaseCard v-else padding="none">
      <table class="min-w-full">
        <caption class="sr-only">Grading scale bands</caption>
        <thead>
          <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
            <th scope="col" class="px-5 py-3">Grade</th>
            <th scope="col" class="px-5 py-3">Range</th>
            <th scope="col" class="px-5 py-3">Grade points</th>
            <th scope="col" class="px-5 py-3 text-right">Result</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-default dark:divide-dark-border">
          <tr v-for="band in saved" :key="band.grade">
            <td class="px-5 py-3">
              <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-small font-semibold" :class="bandTone(band, maxPoints)">{{ band.grade }}</span>
            </td>
            <td class="px-5 py-3 text-small tabular-nums text-ink dark:text-dark-ink">{{ pct(band.min) }} – {{ pct(band.max) }}</td>
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <span class="w-10 text-small font-semibold tabular-nums text-ink dark:text-dark-ink">{{ band.points.toFixed(2) }}</span>
                <span class="hidden h-1.5 w-24 overflow-hidden rounded-pill bg-background sm:block dark:bg-dark-surface-2" aria-hidden="true">
                  <span class="block h-full rounded-pill bg-primary dark:bg-dark-primary" :style="{ width: `${maxPoints ? (band.points / maxPoints) * 100 : 0}%` }" />
                </span>
              </div>
            </td>
            <td class="px-5 py-3 text-right"><StatusBadge :status="band.is_pass ? 'completed' : 'failed'" :label="band.is_pass ? 'Pass' : 'Fail'" /></td>
          </tr>
        </tbody>
      </table>
    </BaseCard>
  </div>
</template>
