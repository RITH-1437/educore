<script setup>
import { Eye, EyeOff, Pencil, Plus, Trash2 } from '@lucide/vue'
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import EmptyState from '../../components/EmptyState.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'
import { examWhen, typeLabel } from '../../utils/exams'

const props = defineProps({
  section: { type: Object, required: true },
  exams: { type: Array, default: () => [] },
  selectedId: { type: Number, default: null },
  roster: { type: Array, default: () => [] },
  totalWeight: { type: Number, default: 0 },
  types: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  canReview: { type: Boolean, default: false },
  today: { type: String, required: true },
})

const { confirm } = useConfirm()
const typeOptions = computed(() => props.types.map((type) => ({ label: typeLabel(type), value: type })))
const selected = computed(() => props.exams.find((exam) => exam.id === props.selectedId) ?? null)
const beforeExam = computed(() => !!selected.value?.scheduled_date && selected.value.scheduled_date > props.today)
const editable = computed(() => props.canManage && !props.section.locked && !beforeExam.value)

const select = (exam) => router.get(`/exams/sections/${props.section.id}`, { exam: exam.id }, { preserveScroll: true, preserveState: false })

// ---- create / edit -----------------------------------------------------------
const editing = ref(null)
const showForm = ref(false)
const blank = { exam_type: 'midterm', title: '', weight: 0, max_score: 100, scheduled_date: '', start_time: '', end_time: '', location: '' }
const form = useForm({ ...blank })

const openCreate = () => {
  editing.value = null
  form.defaults({ ...blank }).reset()
  form.clearErrors()
  showForm.value = true
}
const openEdit = (exam) => {
  editing.value = exam
  form.defaults({
    exam_type: exam.exam_type,
    title: exam.title,
    weight: exam.weight,
    max_score: exam.max_score,
    scheduled_date: exam.scheduled_date ?? '',
    start_time: exam.start_time ?? '',
    end_time: exam.end_time ?? '',
    location: exam.location ?? '',
  }).reset()
  form.clearErrors()
  showForm.value = true
}
const save = () => {
  const options = { preserveScroll: true, onSuccess: () => (showForm.value = false) }
  // Empty strings mean "not set".
  const request = form.transform((data) => Object.fromEntries(Object.entries(data).map(([key, value]) => [key, value === '' ? null : value])))
  editing.value ? request.put(`/exams/${editing.value.id}`, options) : request.post(`/exams/sections/${props.section.id}`, options)
}
const release = async (exam, published) => {
  if (!published || await confirm({ title: 'Release results?', message: 'Students of this section will see their own scores and remarks.', confirmLabel: 'Release' })) {
    router.post(`/exams/${exam.id}/publish`, { published }, { preserveScroll: true })
  }
}
const remove = async (exam) => {
  if (await confirm({ title: `Delete “${exam.title}”?`, message: 'This cannot be undone.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/exams/${exam.id}`, { preserveScroll: true })
  }
}

// ---- results grid ------------------------------------------------------------
const results = useForm({
  results: props.roster.map((row) => ({ enrollment_id: row.enrollment_id, score: row.score ?? '', remarks: row.remarks ?? '' })),
})
const saveResults = () =>
  results
    .transform((data) => ({ results: data.results.map((row) => ({ ...row, score: row.score === '' ? null : row.score, remarks: row.remarks || null })) }))
    .post(`/exams/${selected.value.id}/results`, { preserveScroll: true })

const stats = computed(() => {
  const scores = results.results.map((row) => row.score).filter((score) => score !== '' && score !== null).map(Number)
  if (!scores.length) return null
  return { count: scores.length, average: (scores.reduce((sum, score) => sum + score, 0) / scores.length).toFixed(1), high: Math.max(...scores), low: Math.min(...scores) }
})
const fieldError = (i, field) => results.errors[`results.${i}.${field}`]
</script>

<template>
  <Head :title="`Exams ${section.course.code} ${section.code} - EduCore`" />
  <div class="space-y-6">
    <PageHeader eyebrow="Examinations" :title="`${section.course.code} · Section ${section.code}`" :description="`${section.course.name} · ${section.semester}`">
      <template v-if="canManage && !section.locked" #actions>
        <IconButton :icon="Plus" size="md" variant="primary" label="New exam" @click="openCreate" />
      </template>
    </PageHeader>

    <p v-if="section.locked" class="text-small text-warning">The semester is completed; exams and results are read-only.</p>
    <p v-if="canReview" class="text-small text-muted dark:text-dark-muted">
      Exam weights in this section: <span class="font-semibold tabular-nums" :class="totalWeight > 100 ? 'text-error' : 'text-ink dark:text-dark-ink'">{{ totalWeight }}%</span> of 100%.
      The course grade is built from the course's grading weights; open <span class="font-medium">Grades</span> from the section card.
    </p>

    <BaseCard v-if="!exams.length">
      <EmptyState title="No exams yet" :description="canManage ? 'Plan the midterm, final or quizzes for this section.' : 'Exams appear here once your lecturer schedules them.'" />
    </BaseCard>

    <div v-else class="grid gap-6" :class="canReview ? 'xl:grid-cols-[1fr_2fr]' : ''">
      <!-- Exam list -->
      <ul class="space-y-3" aria-label="Exams">
        <li v-for="exam in exams" :key="exam.id">
          <BaseCard :class="canReview && exam.id === selectedId ? 'ring-2 ring-primary dark:ring-dark-primary' : ''">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <component
                  :is="canReview ? 'button' : 'p'"
                  :type="canReview ? 'button' : undefined"
                  class="text-left text-h4 font-semibold text-ink focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-ink"
                  :class="canReview ? 'hover:text-primary dark:hover:text-dark-primary' : ''"
                  :aria-current="canReview && exam.id === selectedId ? 'true' : undefined"
                  @click="canReview && select(exam)"
                >
                  {{ exam.title }}
                </component>
                <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ examWhen(exam) }}</p>
              </div>
              <BaseBadge variant="muted" size="sm">{{ typeLabel(exam.exam_type) }}</BaseBadge>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-caption text-muted dark:text-dark-muted">
              <span>Weight {{ exam.weight }}% · out of {{ exam.max_score }}</span>
              <StatusBadge :status="exam.is_published ? 'completed' : 'pending'" :label="exam.is_published ? 'Results released' : 'Results not released'" />
              <span v-if="canReview && exam.results_count !== undefined">· {{ exam.results_count }} result{{ exam.results_count === 1 ? '' : 's' }}</span>
            </div>

            <!-- Student: own released result -->
            <p v-if="!canReview && exam.my_result" class="mt-3 text-small text-ink dark:text-dark-ink">
              Your score: <span class="font-semibold tabular-nums">{{ exam.my_result.score ?? '—' }} / {{ exam.max_score }}</span>
              <span v-if="exam.my_result.remarks" class="block text-muted dark:text-dark-muted">{{ exam.my_result.remarks }}</span>
            </p>

            <div v-if="canManage && !section.locked" class="mt-4 flex flex-wrap gap-2">
              <IconButton :icon="Pencil" :label="`Edit ${exam.title}`" @click="openEdit(exam)" />
              <IconButton v-if="!exam.is_published" :icon="Eye" variant="success" :label="`Release results of ${exam.title}`" @click="release(exam, true)" />
              <IconButton v-else :icon="EyeOff" :label="`Hide results of ${exam.title}`" @click="release(exam, false)" />
              <IconButton v-if="!exam.results_count" :icon="Trash2" variant="danger" :label="`Delete ${exam.title}`" @click="remove(exam)" />
            </div>
          </BaseCard>
        </li>
      </ul>

      <!-- Results grid -->
      <BaseCard v-if="canReview && selected" :title="`Results · ${selected.title}`" padding="lg">
        <template #description>
          <span v-if="beforeExam">Results can be entered from the exam date ({{ selected.scheduled_date }}).</span>
          <span v-else-if="editable">Leave a score blank to skip a student; scores are out of {{ selected.max_score }}.</span>
          <span v-else>Read-only.</span>
        </template>

        <EmptyState v-if="!roster.length" title="No students enrolled" description="Students appear here once they enroll in this section." />

        <form v-else @submit.prevent="saveResults">
          <div class="-mx-2 overflow-x-auto">
            <table class="min-w-full">
              <caption class="sr-only">Results for {{ selected.title }}</caption>
              <thead>
                <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
                  <th scope="col" class="px-2 py-2">Student</th>
                  <th scope="col" class="w-32 px-2 py-2">Score</th>
                  <th scope="col" class="px-2 py-2">Remarks</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border-default dark:divide-dark-border">
                <tr v-for="(row, i) in roster" :key="row.enrollment_id" class="align-top">
                  <td class="px-2 py-2">
                    <p class="text-small font-medium text-ink dark:text-dark-ink">{{ row.student.full_name }}</p>
                    <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
                  </td>
                  <td class="px-2 py-2">
                    <BaseInput
                      v-if="editable"
                      v-model="results.results[i].score"
                      :name="`score-${row.enrollment_id}`"
                      :label="`Score for ${row.student.full_name}`"
                      class="[&_label]:sr-only"
                      type="number"
                      :error="fieldError(i, 'score')"
                    />
                    <span v-else class="text-small font-semibold tabular-nums text-ink dark:text-dark-ink">{{ row.score ?? '—' }}</span>
                  </td>
                  <td class="px-2 py-2">
                    <BaseInput
                      v-if="editable"
                      v-model="results.results[i].remarks"
                      :name="`remarks-${row.enrollment_id}`"
                      :label="`Remarks for ${row.student.full_name}`"
                      class="[&_label]:sr-only"
                      :error="fieldError(i, 'remarks')"
                    />
                    <span v-else class="text-small text-muted dark:text-dark-muted">{{ row.remarks ?? '' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-border-default pt-4 dark:border-dark-border">
            <p class="text-caption text-muted dark:text-dark-muted">
              <template v-if="stats">{{ stats.count }} scored · average {{ stats.average }} · high {{ stats.high }} · low {{ stats.low }}</template>
              <template v-else>No scores yet.</template>
            </p>
            <BaseButton v-if="editable" type="submit" :loading="results.processing">Save results</BaseButton>
          </div>
          <ErrorAlert v-if="results.errors.results" class="mt-4" title="Could not save" :message="results.errors.results" />
        </form>
      </BaseCard>
    </div>

    <BaseModal v-model="showForm" :title="editing ? 'Edit exam' : 'New exam'" size="lg">
      <form id="exam-form" class="space-y-4" @submit.prevent="save">
        <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
          <BaseInput v-model="form.title" name="title" label="Title" required :error="form.errors.title" />
          <BaseSelect v-model="form.exam_type" :options="typeOptions" label="Type" :error="form.errors.exam_type" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <BaseInput v-model="form.weight" name="weight" label="Weight (%)" type="number" required :error="form.errors.weight" hint="Exam weights of the section total at most 100%." />
          <BaseInput v-model="form.max_score" name="max_score" label="Max score" type="number" required :error="form.errors.max_score" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <BaseInput v-model="form.scheduled_date" name="scheduled_date" label="Date" type="date" :error="form.errors.scheduled_date" />
          <BaseInput v-model="form.start_time" name="start_time" label="Start" type="time" :error="form.errors.start_time" />
          <BaseInput v-model="form.end_time" name="end_time" label="End" type="time" :error="form.errors.end_time" />
        </div>
        <BaseInput v-model="form.location" name="location" label="Location (optional)" :error="form.errors.location" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
          <BaseButton type="submit" form="exam-form" :loading="form.processing">{{ editing ? 'Save changes' : 'Create exam' }}</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
