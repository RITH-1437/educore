<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  scale: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
})

// Each band only states where it starts; the server derives where it ends.
const form = useForm({
  bands: props.scale.data.map((band) => ({ grade: band.grade, min_percentage: band.min_percentage, grade_point: band.grade_point, is_pass: band.is_pass })),
})
const add = () => form.bands.push({ grade: '', min_percentage: '', grade_point: '', is_pass: true })
const remove = (i) => form.bands.splice(i, 1)
const save = () => form.put('/grading-scale', { preserveScroll: true })
const fieldError = (i, field) => form.errors[`bands.${i}.${field}`]
</script>

<template>
  <Head title="Grading scale - EduCore" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="Grades & GPA" title="Grading scale" :description="`The “${scale.name}” scale maps a course total to a letter grade and grade points.`" />

    <BaseCard v-if="!canEdit" padding="lg">
      <div class="-mx-2 overflow-x-auto">
        <table class="min-w-full">
          <caption class="sr-only">Grading scale</caption>
          <thead>
            <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
              <th scope="col" class="px-2 py-2">Grade</th>
              <th scope="col" class="px-2 py-2">Range</th>
              <th scope="col" class="px-2 py-2 text-right">Grade points</th>
              <th scope="col" class="px-2 py-2">Result</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-default dark:divide-dark-border">
            <tr v-for="band in scale.data" :key="band.grade">
              <td class="px-2 py-2 text-small font-semibold text-ink dark:text-dark-ink">{{ band.grade }}</td>
              <td class="px-2 py-2 text-small tabular-nums">{{ band.min_percentage }}% – {{ band.max_percentage }}%</td>
              <td class="px-2 py-2 text-right text-small tabular-nums">{{ band.grade_point.toFixed(2) }}</td>
              <td class="px-2 py-2"><StatusBadge :status="band.is_pass ? 'completed' : 'failed'" :label="band.is_pass ? 'Pass' : 'Fail'" /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </BaseCard>

    <BaseCard v-else padding="lg" title="Bands">
      <template #description>Give each grade its minimum percentage. The lowest band starts at 0%; higher bands need at least as many grade points. Approved grades keep their letters; drafts use the new scale when recomputed.</template>
      <form class="space-y-3" @submit.prevent="save">
        <div v-for="(band, i) in form.bands" :key="i" class="grid items-start gap-3 sm:grid-cols-[1fr_1fr_1fr_auto_auto]">
          <BaseInput v-model="band.grade" :name="`grade-${i}`" label="Grade" required :error="fieldError(i, 'grade')" />
          <BaseInput v-model="band.min_percentage" :name="`min-${i}`" label="From (%)" type="number" required :error="fieldError(i, 'min_percentage')" />
          <BaseInput v-model="band.grade_point" :name="`point-${i}`" label="Grade points" type="number" required :error="fieldError(i, 'grade_point')" />
          <label class="flex items-center gap-2 pt-8 text-small text-ink dark:text-dark-ink">
            <input v-model="band.is_pass" type="checkbox" class="size-4 rounded border-border-default text-primary focus-visible:outline-2 focus-visible:outline-primary" />
            Pass
          </label>
          <BaseButton class="sm:mt-6" size="sm" variant="ghost" :disabled="form.bands.length <= 2" :aria-label="`Remove grade ${band.grade}`" @click="remove(i)">Remove</BaseButton>
        </div>

        <ErrorAlert v-if="form.errors.bands" title="Could not save" :message="form.errors.bands" />

        <div class="flex flex-wrap justify-between gap-2 border-t border-border-default pt-4 dark:border-dark-border">
          <BaseButton variant="secondary" @click="add">Add band</BaseButton>
          <BaseButton type="submit" :loading="form.processing">Save scale</BaseButton>
        </div>
      </form>
    </BaseCard>
  </div>
</template>
