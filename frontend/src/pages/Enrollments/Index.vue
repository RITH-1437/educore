<script setup>
import IconButton from '../../components/IconButton.vue'
import { exportUrl } from '../../utils/exports'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { CircleCheckBig, Download, Search, UserMinus, UserPlus } from '@lucide/vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  enrollments: { type: Object, required: true },
  semesters: { type: Array, default: () => [] },
  openSections: { type: Array, default: () => [] },
  students: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const { confirm } = useConfirm()
// Faculty Admin reads only; write controls are hidden (backend still enforces).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))
const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)

const search = ref(props.filters.search ?? '')
const semesterId = ref(props.filters.semester_id ?? '')
const status = ref(props.filters.status ?? '')
// Exports what the list currently shows (the applied filters, not unsaved input).
const csvUrl = computed(() => exportUrl('/enrollments/export', { search: props.filters.search, filters: { semester_id: props.filters.semester_id, section_id: props.filters.section_id, status: props.filters.status } }))
const applyFilters = () => router.get('/enrollments', { search: search.value || undefined, semester_id: semesterId.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true })

const semesterOptions = computed(() => props.semesters.map((s) => ({ value: s.id, label: s.label })))
const statusOptions = computed(() => props.statuses.map((value) => ({ value, label: label(value) })))
const sectionOptions = computed(() => props.openSections.map((s) => ({ value: s.id, label: `${s.label} (${s.seats} seats left)` })))
const studentOptions = computed(() => props.students.map((s) => ({ value: s.id, label: s.label })))

const form = useForm({ student_id: '', section_id: '' })
const enroll = () => form.post('/enrollments', { preserveScroll: true, onSuccess: () => form.reset('section_id') })

const drop = async (row) => {
  if (await confirm({ title: 'Drop enrollment?', message: `Drop ${row.student?.full_name} from ${row.section?.course?.code} ${row.section?.code}? The record is kept (withdrawn if attendance or a grade exists).`, confirmLabel: 'Drop', destructive: true })) {
    router.post(`/enrollments/${row.id}/drop`, {}, { preserveScroll: true })
  }
}
const complete = (row) => router.post(`/enrollments/${row.id}/complete`, {}, { preserveScroll: true })

const columns = [
  { key: 'student', label: 'Student' },
  { key: 'course', label: 'Course / section' },
  { key: 'semester', label: 'Semester' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Enrollments - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Academics" title="Enrollments" description="Course registrations. Every enrollment — admin or student — passes the same checks: active student, open registration, prerequisites, credit limit and seats.">
      <template #actions><IconButton :icon="Download" :href="csvUrl" native size="md" label="Export CSV" /></template>
    </PageHeader>

    <BaseCard v-if="canManage" title="Enroll a student" padding="lg">
      <form class="grid gap-4 lg:grid-cols-[2fr_3fr_auto] lg:items-end" @submit.prevent="enroll">
        <BaseSelect v-model="form.student_id" label="Student" :options="studentOptions" placeholder="Select an active student" :error="form.errors.student_id" />
        <BaseSelect v-model="form.section_id" label="Section" :options="sectionOptions" placeholder="Select an open section" :error="form.errors.section_id" />
        <IconButton :icon="UserPlus" type="submit" size="md" variant="primary" label="Enroll student" :loading="form.processing" :disabled="!form.student_id || !form.section_id" />
      </form>
    </BaseCard>

    <BaseCard padding="sm">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_1fr_auto] lg:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search" placeholder="Student ID or name" />
        <BaseSelect v-model="semesterId" label="Semester" :options="semesterOptions" placeholder="All semesters" />
        <BaseSelect v-model="status" label="Status" :options="statusOptions" placeholder="All" />
        <IconButton :icon="Search" type="submit" size="md" label="Apply filters" />
      </form>
    </BaseCard>

    <BaseTable :columns="columns" :rows="enrollments.data" caption="Enrollments" empty-title="No enrollments" empty-description="Enroll a student into an open section to get started.">
      <template #cell-student="{ row }">
        <p class="font-medium">{{ row.student?.full_name }}</p>
        <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student?.student_number }}</p>
      </template>
      <template #cell-course="{ row }"><span class="font-semibold">{{ row.section?.course?.code }}</span> · {{ row.section?.code }}</template>
      <template #cell-semester="{ row }"><span class="whitespace-nowrap text-muted dark:text-dark-muted">{{ row.semester?.academic_year }} · {{ row.semester?.name }}</span></template>
      <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
      <template #cell-actions="{ row }">
        <div v-if="canManage && ['pending', 'confirmed'].includes(row.status)" class="flex justify-end gap-4">
          <IconButton v-if="row.status === 'confirmed'" :icon="CircleCheckBig" variant="success" label="Mark completed" @click="complete(row)" />
          <IconButton :icon="UserMinus" variant="danger" label="Drop enrollment" @click="drop(row)" />
        </div>
      </template>
    </BaseTable>

    <Pagination :links="enrollments.meta?.links ?? []" />
  </div>
</template>
