<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { BookOpen, Search, UserRound } from '@lucide/vue'
import { computed, ref } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import { useLiveFilters } from '../../composables/useLiveFilters'

// Public course catalog (report 53): departments as categories, course search
// and the courses taught this semester — the front page of a Moodle site.
const props = defineProps({
  courses: { type: Object, required: true },
  departments: { type: Array, default: () => [] },
  semester: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const departmentId = ref(props.filters.department_id ?? '')
const offered = ref(Boolean(props.filters.offered))

const totalCourses = computed(() => props.departments.reduce((sum, d) => sum + d.courses_count, 0))
const selectedDepartment = computed(() => props.departments.find((d) => d.id === departmentId.value) ?? null)

const apply = (options) => router.get('/catalog', {
  search: search.value || undefined,
  filters: departmentId.value || offered.value ? { department_id: departmentId.value || undefined, offered: offered.value ? 1 : undefined } : undefined,
}, { preserveState: true, replace: true, ...options })
const { applyNow, searching } = useLiveFilters(apply, { text: [search], choices: [departmentId, offered] })

const pick = (id) => { departmentId.value = departmentId.value === id ? '' : id }
const credits = (value) => `${Number(value)} credit${Number(value) === 1 ? '' : 's'}`
</script>

<script>
import DefaultLayout from '../../layouts/DefaultLayout.vue'
import PublicLayout from '../../layouts/PublicLayout.vue'

// Signed in: inside the app; a visitor: the public layout.
export default { layout: (h, page) => h(page.props.auth?.user ? DefaultLayout : PublicLayout, () => page) }
</script>

<template>
  <Head title="Course catalog" />
  <div class="space-y-8">
    <PageHeader
      eyebrow="Course catalog"
      title="Courses"
      :description="`${totalCourses} course${totalCourses === 1 ? '' : 's'} in ${departments.length} department${departments.length === 1 ? '' : 's'}.${semester ? ` Sections and lecturers shown for ${semester.name}.` : ''}`"
    />

    <form class="flex flex-col gap-3 sm:flex-row sm:items-end" role="search" @submit.prevent="applyNow">
      <BaseInput v-model="search" name="search" type="search" label="Search courses" placeholder="Code, name or topic" class="sm:max-w-md sm:flex-1" :loading="searching">
        <template #leading><Search class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>
      <label class="flex min-h-11 cursor-pointer items-center gap-2 text-small text-ink dark:text-dark-ink">
        <input v-model="offered" type="checkbox" class="h-4 w-4 rounded border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
        Offered this semester only
      </label>
    </form>

    <!-- Departments as categories -->
    <section v-if="departments.length" aria-labelledby="categories-heading">
      <h2 id="categories-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Departments</h2>
      <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <button
          v-for="department in departments"
          :key="department.id"
          type="button"
          class="flex min-h-20 items-start gap-3 rounded-xl border p-4 text-left transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
          :class="departmentId === department.id
            ? 'border-primary bg-primary/5 dark:border-dark-primary dark:bg-dark-primary/10'
            : 'border-border-default bg-surface hover:border-primary/50 dark:border-dark-border dark:bg-dark-surface dark:hover:border-dark-primary/50'"
          :aria-pressed="departmentId === department.id"
          @click="pick(department.id)"
        >
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-caption font-bold text-primary dark:bg-dark-primary/15 dark:text-dark-primary">{{ department.code?.slice(0, 4) }}</span>
          <span class="min-w-0">
            <span class="block text-small font-semibold text-ink dark:text-dark-ink">{{ department.name }}</span>
            <span class="text-caption text-muted dark:text-dark-muted">{{ department.courses_count }} course{{ department.courses_count === 1 ? '' : 's' }}</span>
          </span>
        </button>
      </div>
    </section>

    <section aria-labelledby="courses-heading">
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="courses-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">
          {{ selectedDepartment ? selectedDepartment.name : offered ? 'Available this semester' : 'All courses' }}
        </h2>
        <p class="text-small text-muted dark:text-dark-muted" aria-live="polite">{{ courses.meta.total }} result{{ courses.meta.total === 1 ? '' : 's' }}</p>
      </div>

      <BaseCard v-if="!courses.data.length" class="mt-3">
        <EmptyState title="No courses found" description="Try another search, department, or include courses not offered this semester." />
      </BaseCard>

      <ul v-else class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <li v-for="course in courses.data" :key="course.id">
          <Link :href="`/catalog/courses/${course.id}`" class="group block h-full rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <BaseCard class="flex h-full flex-col transition-shadow duration-150 group-hover:shadow-md">
              <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-small font-semibold text-primary dark:text-dark-primary">{{ course.code }}</span>
                <BaseBadge variant="muted" size="sm">{{ credits(course.credits) }}</BaseBadge>
              </div>
              <h3 class="mt-2 text-body font-semibold text-ink group-hover:text-primary dark:text-dark-ink dark:group-hover:text-dark-primary">{{ course.name }}</h3>
              <p class="text-caption text-muted dark:text-dark-muted">{{ course.department?.name }}</p>
              <p v-if="course.description" class="mt-2 line-clamp-3 text-small text-muted dark:text-dark-muted">{{ course.description }}</p>
              <div class="mt-auto space-y-2 pt-4">
                <p v-if="course.lecturers.length" class="flex items-start gap-1.5 text-caption text-muted dark:text-dark-muted">
                  <UserRound class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" /><span>{{ course.lecturers.join(', ') }}</span>
                </p>
                <BaseBadge v-if="course.offered" variant="success" size="sm"><BookOpen class="h-3 w-3" aria-hidden="true" /> Offered {{ semester?.name }}</BaseBadge>
                <BaseBadge v-else variant="muted" size="sm">Not offered this semester</BaseBadge>
              </div>
            </BaseCard>
          </Link>
        </li>
      </ul>

      <Pagination class="mt-6" :links="courses.meta?.links ?? []" />
    </section>
  </div>
</template>
