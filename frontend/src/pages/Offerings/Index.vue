<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useLiveFilters } from '../../composables/useLiveFilters'
import { Eye, Plus, Settings2 } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  offerings: { type: Object, required: true },
  semesters: { type: Array, default: () => [] },
  courses: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  // From `CourseOfferingPolicy`: managers, and a Department Admin for their department's courses.
  canManage: { type: Boolean, default: false },
})

const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)

const search = ref(props.filters.search ?? '')
const semesterId = ref(props.filters.semester_id ?? '')
const status = ref(props.filters.status ?? '')

const semesterOptions = computed(() => props.semesters.map((semester) => ({ value: semester.id, label: semester.label + (semester.completed ? ' (completed)' : '') })))
const openSemesterOptions = computed(() => props.semesters.filter((semester) => !semester.completed).map((semester) => ({ value: semester.id, label: semester.label })))
const courseOptions = computed(() => props.courses.map((course) => ({ value: course.id, label: `${course.code} — ${course.name}` })))
const statusOptions = computed(() => props.statuses.map((value) => ({ value, label: label(value) })))
const hasFilters = computed(() => Boolean(search.value || semesterId.value || status.value))

const applyFilters = (options = {}) => router.get('/offerings', { search: search.value || undefined, semester_id: semesterId.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true, ...options })

const columns = [
  { key: 'course', label: 'Course' },
  { key: 'semester', label: 'Semester' },
  { key: 'sections_count', label: 'Sections', align: 'center' },
  { key: 'total_capacity', label: 'Seats', align: 'center' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' },
]

const showCreate = ref(false)
const form = useForm({ course_id: '', semester_id: '', status: 'draft', max_enrollments: '' })
const submit = () => form.post('/offerings', { preserveScroll: true, onSuccess: () => { showCreate.value = false } })
// Soft search: the list follows the filters as they change — typed text after a
// short pause, picked options at once — so there is no search button.
const { applyNow, searching } = useLiveFilters(applyFilters, { text: [search], choices: [semesterId, status] })
</script>

<template>
  <Head title="Offerings & sections - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Academics" title="Offerings & sections" description="Courses offered in a semester, split into sections with capacity, lecturers, rooms and weekly class times.">
      <template v-if="canManage" #actions>
        <IconButton :icon="Plus" size="md" variant="primary" label="New offering" @click="form.reset(); form.clearErrors(); showCreate = true" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_1fr] lg:items-end" @submit.prevent="applyNow">
        <BaseInput v-model="search" :loading="searching" label="Search" placeholder="Course code or name" />
        <BaseSelect v-model="semesterId" label="Semester" :options="semesterOptions" placeholder="All semesters" />
        <BaseSelect v-model="status" label="Status" :options="statusOptions" placeholder="All" />
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="offerings.data"
      caption="Course offerings"
      :empty-title="hasFilters ? 'No offerings match your filters' : 'No offerings yet'"
      :empty-description="hasFilters ? 'Try different filters.' : 'Offer an active course in a semester, then add its sections.'"
    >
      <template #cell-course="{ row }">
        <p class="font-semibold">{{ row.course?.code }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.course?.name }}</p>
      </template>
      <template #cell-semester="{ row }">
        <span class="whitespace-nowrap">{{ row.semester?.academic_year?.code }} · {{ row.semester?.name }}</span>
      </template>
      <template #cell-sections_count="{ row }"><span class="tabular-nums">{{ row.sections_count ?? 0 }}</span></template>
      <template #cell-total_capacity="{ row }"><span class="tabular-nums">{{ row.total_capacity ?? 0 }}</span></template>
      <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
      <template #cell-actions="{ row }">
        <div class="flex justify-end"><IconButton :icon="canManage ? Settings2 : Eye" :href="`/offerings/${row.id}`" :label="canManage ? 'Manage offering' : 'View offering'" /></div>
      </template>
    </BaseTable>

    <Pagination :links="offerings.meta?.links ?? []" />

    <BaseModal v-model="showCreate" title="New offering" size="md">
      <form class="space-y-5" @submit.prevent="submit">
        <BaseSelect v-model="form.course_id" label="Course" :options="courseOptions" placeholder="Select an active course" :error="form.errors.course_id" required />
        <BaseSelect v-model="form.semester_id" label="Semester" :options="openSemesterOptions" placeholder="Select a semester" :error="form.errors.semester_id" required />
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseSelect v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
          <BaseInput v-model="form.max_enrollments" name="max_enrollments" label="Max enrollments" type="number" min="1" placeholder="Optional" :error="form.errors.max_enrollments" />
        </div>
        <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />
        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Create offering</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
