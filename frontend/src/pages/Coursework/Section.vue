<script setup>
import { Pencil, Trash2 } from '@lucide/vue'
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import EmptyState from '../../components/EmptyState.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import SubmitWork from '../../components/coursework/SubmitWork.vue'
import { useConfirm } from '../../composables/useConfirm'
import { formatDue, toLocalInput } from '../../utils/coursework'

const props = defineProps({
  section: { type: Object, required: true },
  assignments: { type: Array, default: () => [] },
  submissions: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  canReview: { type: Boolean, default: false },
  acceptedTypes: { type: Array, default: () => [] },
  maxKb: { type: Number, default: 10240 },
})

const { confirm } = useConfirm()
const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)
const typeOptions = computed(() => props.types.map((type) => ({ label: label(type), value: type })))

// ---- create / edit -----------------------------------------------------------
const editing = ref(null)
const showForm = ref(false)
const blank = { title: '', description: '', instructions: '', max_score: 100, due_at: '', assignment_type: 'homework' }
const form = useForm({ ...blank })

const openCreate = () => {
  editing.value = null
  form.defaults({ ...blank }).reset()
  form.clearErrors()
  showForm.value = true
}
const openEdit = (assignment) => {
  editing.value = assignment
  form.defaults({
    title: assignment.title,
    description: assignment.description ?? '',
    instructions: assignment.instructions ?? '',
    max_score: assignment.max_score,
    due_at: toLocalInput(assignment.due_at),
    assignment_type: assignment.assignment_type,
  }).reset()
  form.clearErrors()
  showForm.value = true
}
const save = () => {
  const options = { preserveScroll: true, onSuccess: () => (showForm.value = false) }
  // datetime-local is the viewer's local time; send an absolute instant.
  const request = form.transform((data) => ({ ...data, due_at: data.due_at ? new Date(data.due_at).toISOString() : '' }))
  editing.value ? request.put(`/assignments/${editing.value.id}`, options) : request.post(`/coursework/sections/${props.section.id}`, options)
}

const publish = async (assignment, published) => {
  if (published || await confirm({ title: 'Unpublish assignment?', message: 'Students will no longer see it.', confirmLabel: 'Unpublish' })) {
    router.post(`/assignments/${assignment.id}/publish`, { published }, { preserveScroll: true })
  }
}
const remove = async (assignment) => {
  if (await confirm({ title: `Delete “${assignment.title}”?`, message: 'This cannot be undone.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/assignments/${assignment.id}`, { preserveScroll: true })
  }
}

// ---- grading -----------------------------------------------------------------
const open = ref(null)
const grading = ref(null)
const gradeForm = useForm({ score: '', feedback: '' })
const rows = (assignment) => props.submissions[assignment.id] ?? []
const startGrade = (submission) => {
  grading.value = submission
  gradeForm.defaults({ score: submission.score ?? '', feedback: submission.feedback ?? '' }).reset()
  gradeForm.clearErrors()
}
const saveGrade = () => gradeForm.post(`/submissions/${grading.value.id}/grade`, { preserveScroll: true, onSuccess: () => (grading.value = null) })

const badge = (status) => ({ submitted: 'active', late: 'pending', graded: 'completed', returned: 'completed' })[status] ?? 'pending'
</script>

<template>
  <Head :title="`Coursework ${section.course.code} ${section.code} - EduCore`" />
  <div class="space-y-6">
    <PageHeader eyebrow="Coursework" :title="`${section.course.code} · Section ${section.code}`" :description="`${section.course.name} · ${section.semester}`">
      <template v-if="canManage" #actions>
        <BaseButton @click="openCreate">New assignment</BaseButton>
      </template>
    </PageHeader>

    <BaseCard v-if="!assignments.length">
      <EmptyState title="No assignments yet" :description="canManage ? 'Create one — it stays a draft until you publish it.' : 'Assignments appear here once they are published.'" />
    </BaseCard>

    <BaseCard v-for="assignment in assignments" :key="assignment.id" padding="lg">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ assignment.title }}</h2>
            <BaseBadge variant="muted" size="sm">{{ assignment.assignment_type }}</BaseBadge>
            <StatusBadge v-if="canManage || canReview" :status="assignment.is_published ? 'active' : 'draft'" :label="assignment.is_published ? 'Published' : 'Draft'" />
          </div>
          <p class="mt-1 text-small" :class="assignment.past_due ? 'text-error dark:text-red-300' : 'text-muted dark:text-dark-muted'">
            Due {{ formatDue(assignment.due_at) }} · {{ assignment.max_score }} points
          </p>
        </div>
        <div v-if="canManage" class="flex flex-wrap gap-2">
          <IconButton :icon="Pencil" :label="`Edit ${assignment.title}`" @click="openEdit(assignment)" />
          <BaseButton v-if="!assignment.is_published" size="sm" @click="publish(assignment, true)">Publish</BaseButton>
          <BaseButton v-else-if="!rows(assignment).length" size="sm" variant="ghost" @click="publish(assignment, false)">Unpublish</BaseButton>
          <IconButton v-if="!rows(assignment).length" :icon="Trash2" variant="danger" :label="`Delete ${assignment.title}`" @click="remove(assignment)" />
        </div>
      </div>

      <p v-if="assignment.description" class="mt-3 text-small text-ink dark:text-dark-ink">{{ assignment.description }}</p>
      <p v-if="assignment.instructions" class="mt-2 whitespace-pre-line text-small text-muted dark:text-dark-muted">{{ assignment.instructions }}</p>

      <!-- Student: own submission -->
      <div v-if="!canReview && assignment.my_submission !== undefined" class="mt-4 border-t border-border-default pt-4 dark:border-dark-border">
        <SubmitWork :assignment="assignment" :submission="assignment.my_submission" :accepted-types="acceptedTypes" :max-kb="maxKb" />
      </div>

      <!-- Staff: submissions -->
      <div v-if="canReview" class="mt-4 border-t border-border-default pt-4 dark:border-dark-border">
        <button
          type="button"
          class="text-small font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-primary"
          :aria-expanded="open === assignment.id"
          @click="open = open === assignment.id ? null : assignment.id"
        >
          {{ rows(assignment).length }} submission{{ rows(assignment).length === 1 ? '' : 's' }} · {{ rows(assignment).filter((row) => row.status === 'graded').length }} graded
        </button>

        <ul v-if="open === assignment.id" class="mt-3 divide-y divide-border-default dark:divide-dark-border">
          <li v-if="!rows(assignment).length" class="py-2 text-small text-muted dark:text-dark-muted">Nothing submitted yet.</li>
          <li v-for="row in rows(assignment)" :key="row.id" class="flex flex-wrap items-center justify-between gap-3 py-2">
            <div class="min-w-0">
              <p class="text-small font-medium text-ink dark:text-dark-ink">{{ row.student?.full_name }} <span class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student?.student_number }}</span></p>
              <p class="text-caption text-muted dark:text-dark-muted">
                {{ formatDue(row.submitted_at) }}
                <template v-if="row.file"> · <a :href="row.file.download_url" class="text-primary hover:underline dark:text-dark-primary">{{ row.file.name }}</a></template>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <StatusBadge :status="badge(row.status)" :label="label(row.status)" />
              <span v-if="row.score !== null" class="text-small font-semibold tabular-nums text-ink dark:text-dark-ink">{{ row.score }} / {{ assignment.max_score }}</span>
              <BaseButton v-if="canManage" size="sm" variant="secondary" @click="startGrade({ ...row, max: assignment.max_score })">{{ row.score === null ? 'Grade' : 'Regrade' }}</BaseButton>
            </div>
          </li>
        </ul>
      </div>
    </BaseCard>

    <BaseModal v-model="showForm" :title="editing ? 'Edit assignment' : 'New assignment'" size="lg">
      <form id="assignment-form" class="space-y-4" @submit.prevent="save">
        <BaseInput v-model="form.title" name="title" label="Title" required :error="form.errors.title" />
        <div class="grid gap-4 sm:grid-cols-3">
          <BaseSelect v-model="form.assignment_type" :options="typeOptions" label="Type" :error="form.errors.assignment_type" />
          <BaseInput v-model="form.max_score" name="max_score" label="Max score" type="number" required :error="form.errors.max_score" />
          <BaseInput v-model="form.due_at" name="due_at" label="Due" type="datetime-local" required :error="form.errors.due_at" />
        </div>
        <BaseTextarea v-model="form.description" label="Description" :rows="2" :error="form.errors.description" />
        <BaseTextarea v-model="form.instructions" label="Instructions" :rows="4" :error="form.errors.instructions" />
        <ErrorAlert v-if="form.errors.assignment" title="Could not save" :message="form.errors.assignment" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
          <BaseButton type="submit" form="assignment-form" :loading="form.processing">{{ editing ? 'Save changes' : 'Create draft' }}</BaseButton>
        </div>
      </template>
    </BaseModal>

    <BaseModal :model-value="grading !== null" :title="grading ? `Grade · ${grading.student?.full_name}` : ''" @update:model-value="grading = null">
      <form v-if="grading" id="grade-form" class="space-y-4" @submit.prevent="saveGrade">
        <BaseInput v-model="gradeForm.score" name="score" :label="`Score (out of ${grading.max})`" type="number" required :error="gradeForm.errors.score" />
        <BaseTextarea v-model="gradeForm.feedback" label="Feedback (optional)" :rows="4" :error="gradeForm.errors.feedback" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="grading = null">Cancel</BaseButton>
          <BaseButton type="submit" form="grade-form" :loading="gradeForm.processing">Save grade</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
