<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import EmptyState from '../../components/EmptyState.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'
import { COMPONENTS, componentLabel, gradeStatus, gradeStatusLabel, percent } from '../../utils/grades'

const props = defineProps({
  section: { type: Object, required: true },
  sheet: { type: Object, required: true },
  scale: { type: Array, default: () => [] },
  canGrade: { type: Boolean, default: false },
  canApprove: { type: Boolean, default: false },
})

const { confirm } = useConfirm()
const rows = computed(() => props.sheet.data)
const counts = computed(() => props.sheet.counts)
// Only components with something to measure are shown; their weights are scaled to 100%.
const shown = computed(() => COMPONENTS.filter((component) => props.sheet.components[component] && props.sheet.weights[component] > 0))
const usedWeight = computed(() => shown.value.reduce((sum, component) => sum + props.sheet.weights[component], 0))
const editable = (row) => !row.grade || row.grade.status === 'draft'

const form = useForm({
  remarks: props.sheet.data.map((row) => ({ enrollment_id: row.enrollment_id, remarks: row.grade?.remarks ?? '' })),
})
const base = `/grades/sections/${props.section.id}`
const compute = () => form.post(base, { preserveScroll: true })

const act = async (action, { title, message, confirmLabel, destructive = false }) => {
  if (await confirm({ title, message, confirmLabel, destructive })) {
    router.post(`${base}/${action}`, {}, { preserveScroll: true })
  }
}
const submit = () => act('submit', { title: 'Submit grades for approval?', message: 'Submitted grades can no longer be recomputed unless an administrator returns them.', confirmLabel: 'Submit' })
const approve = () => act('approve', { title: 'Approve grades?', message: 'Approved grades count toward GPA and prerequisites, and students can see them.', confirmLabel: 'Approve' })
const sendBack = () => act('return', { title: 'Return grades to draft?', message: 'Submitted and approved grades go back to the lecturer; affected GPAs are recalculated.', confirmLabel: 'Return to draft', destructive: true })

// Submit rejections (missing / blank grades) arrive as a `grades` page error.
const page = usePage()
const errors = computed(() => page.props.errors?.grades)
</script>

<template>
  <Head :title="`Grades ${section.course.code} ${section.code} - EduCore`" />
  <div class="space-y-6">
    <PageHeader eyebrow="Grades & GPA" :title="`${section.course.code} · Section ${section.code}`" :description="`${section.course.name} · ${section.semester} · ${section.course.credits} credits`">
      <template #actions>
        <BaseButton v-if="canGrade && counts.students" variant="secondary" :loading="form.processing" @click="compute">Compute drafts</BaseButton>
        <BaseButton v-if="canGrade && counts.draft" @click="submit">Submit for approval</BaseButton>
        <BaseButton v-if="canApprove && counts.submitted" @click="approve">Approve</BaseButton>
        <BaseButton v-if="canApprove && (counts.submitted || counts.approved)" variant="ghost" @click="sendBack">Return to draft</BaseButton>
      </template>
    </PageHeader>

    <BaseCard>
      <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-small text-muted dark:text-dark-muted">
        <span>
          Weights:
          <template v-for="(component, i) in COMPONENTS" :key="component">
            <span :class="shown.includes(component) ? 'text-ink dark:text-dark-ink' : 'line-through'">{{ componentLabel(component) }} {{ sheet.weights[component] }}%</span><span v-if="i < COMPONENTS.length - 1"> · </span>
          </template>
        </span>
        <span v-if="usedWeight && usedWeight !== 100">Struck-out components have nothing recorded yet; the others ({{ usedWeight }}%) are scaled to 100%.</span>
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        <StatusBadge status="draft" :label="`${counts.draft} draft`" />
        <StatusBadge status="pending" :label="`${counts.submitted} awaiting approval`" />
        <StatusBadge status="approved" :label="`${counts.approved} approved`" />
        <span class="text-caption text-muted dark:text-dark-muted">of {{ counts.students }} students</span>
      </div>
    </BaseCard>

    <ErrorAlert v-if="errors" title="Could not submit" :message="errors" />

    <BaseCard v-if="!rows.length">
      <EmptyState title="No students to grade" description="Confirmed students of this section appear here." />
    </BaseCard>

    <BaseCard v-else padding="lg" title="Grade sheet">
      <template #description>Live totals from recorded attendance, coursework and exam results. Compute drafts to store them.</template>
      <div class="-mx-2 overflow-x-auto">
        <table class="min-w-full">
          <caption class="sr-only">Grades for {{ section.course.code }} section {{ section.code }}</caption>
          <thead>
            <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
              <th scope="col" class="px-2 py-2">Student</th>
              <th v-for="component in shown" :key="component" scope="col" class="px-2 py-2 text-right">{{ componentLabel(component) }}</th>
              <th scope="col" class="px-2 py-2 text-right">Total</th>
              <th scope="col" class="px-2 py-2">Grade</th>
              <th scope="col" class="px-2 py-2">Status</th>
              <th scope="col" class="px-2 py-2">Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-default dark:divide-dark-border">
            <tr v-for="(row, i) in rows" :key="row.enrollment_id" class="align-top">
              <td class="px-2 py-2">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ row.student.full_name }}</p>
                <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
              </td>
              <td v-for="component in shown" :key="component" class="px-2 py-2 text-right text-small tabular-nums text-ink dark:text-dark-ink">{{ percent(row.components[component]) }}</td>
              <td class="px-2 py-2 text-right text-small font-semibold tabular-nums text-ink dark:text-dark-ink">
                {{ percent(row.grade && !editable(row) ? row.grade.total_score : row.computed.total) }}
              </td>
              <td class="px-2 py-2 text-small text-ink dark:text-dark-ink">
                <template v-if="row.grade && !editable(row)">{{ row.grade.letter_grade ?? '—' }} <span class="text-muted dark:text-dark-muted">({{ row.grade.grade_point ?? '—' }})</span></template>
                <template v-else>{{ row.computed.letter ?? '—' }} <span v-if="row.computed.letter" class="text-muted dark:text-dark-muted">({{ row.computed.grade_point }})</span></template>
              </td>
              <td class="px-2 py-2">
                <StatusBadge v-if="row.grade" :status="gradeStatus(row.grade.status)" :label="gradeStatusLabel(row.grade.status)" />
                <span v-else class="text-caption text-muted dark:text-dark-muted">Not computed</span>
              </td>
              <td class="px-2 py-2">
                <BaseInput
                  v-if="canGrade && editable(row)"
                  v-model="form.remarks[i].remarks"
                  :name="`remarks-${row.enrollment_id}`"
                  :label="`Remarks for ${row.student.full_name}`"
                  class="[&_label]:sr-only"
                  :error="form.errors[`remarks.${i}.remarks`]"
                />
                <span v-else class="text-small text-muted dark:text-dark-muted">{{ row.grade?.remarks ?? '' }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="mt-4 text-caption text-muted dark:text-dark-muted">
        Scale:
        <span v-for="(band, i) in scale" :key="band.grade">{{ band.grade }} ≥ {{ band.min_percentage }}%<span v-if="i < scale.length - 1"> · </span></span>
      </p>
    </BaseCard>
  </div>
</template>
