<script setup>
import IconButton from '../IconButton.vue'
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Plus, Trash2 } from '@lucide/vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import EmptyState from '../EmptyState.vue'
import StatusBadge from '../StatusBadge.vue'

// The program's curriculum (program <-> course membership). Membership lives
// with the program (`skills/program-management/SKILL.md`); the course catalog
// itself is managed from /courses.
const props = defineProps({
  program: { type: Object, required: true },
  availableCourses: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
})

const form = useForm({ course_id: '', is_required: true, suggested_semester: '' })

const courseOptions = computed(() => props.availableCourses.map((course) => ({ value: course.id, label: `${course.code} — ${course.name} (${course.credits} cr)` })))
const courses = computed(() => props.program.courses ?? [])
const totalCredits = computed(() => courses.value.reduce((sum, course) => sum + course.credits, 0))
const base = computed(() => `/programs/${props.program.id}/courses`)

const add = () =>
  form.post(base.value, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })

// Placement edits save as soon as they change; the server validates the range.
const setRequired = (course, value) => router.put(`${base.value}/${course.id}`, { is_required: value }, { preserveScroll: true })
const setSemester = (course, event) => {
  const value = event.target.value
  router.put(`${base.value}/${course.id}`, { suggested_semester: value === '' ? null : Number(value) }, { preserveScroll: true })
}
const remove = (course) => router.delete(`${base.value}/${course.id}`, { preserveScroll: true })
</script>

<template>
  <BaseCard title="Curriculum" padding="lg">
    <template #description>
      Courses in this program<template v-if="program.credits_required"> — {{ totalCredits }} of {{ program.credits_required }} required credits planned</template>.
    </template>

    <div v-if="courses.length" class="-mx-2 overflow-x-auto">
      <table class="min-w-full">
        <caption class="sr-only">Program curriculum</caption>
        <thead>
          <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
            <th scope="col" class="px-2 py-2">Course</th>
            <th scope="col" class="px-2 py-2 text-center">Credits</th>
            <th scope="col" class="px-2 py-2">Required</th>
            <th scope="col" class="px-2 py-2">Semester</th>
            <th v-if="canManage" scope="col" class="px-2 py-2 text-right"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-default dark:divide-dark-border">
          <tr v-for="course in courses" :key="course.id">
            <td class="px-2 py-3">
              <Link :href="`/courses/${course.id}/edit`" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">{{ course.code }}</Link>
              <span class="ml-1 text-small text-ink dark:text-dark-ink">{{ course.name }}</span>
              <StatusBadge v-if="course.status !== 'active'" class="ml-2" :status="course.status" />
            </td>
            <td class="px-2 py-3 text-center text-small tabular-nums">{{ course.credits }}</td>
            <td class="px-2 py-3">
              <label v-if="canManage" class="inline-flex min-h-9 items-center gap-2 text-small">
                <input type="checkbox" :checked="course.is_required" class="h-4 w-4 rounded-sm border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary" :aria-label="`${course.code} is required`" @change="setRequired(course, $event.target.checked)" />
                <span class="sr-only sm:not-sr-only">{{ course.is_required ? 'Required' : 'Elective' }}</span>
              </label>
              <span v-else class="text-small">{{ course.is_required ? 'Required' : 'Elective' }}</span>
            </td>
            <td class="px-2 py-3">
              <input
                v-if="canManage"
                type="number"
                min="1"
                max="16"
                :value="course.suggested_semester ?? ''"
                class="h-9 w-20 rounded-md border border-border-default bg-surface/70 px-2 text-small focus-visible:outline-2 focus-visible:outline-primary dark:border-dark-border dark:bg-dark-surface"
                :aria-label="`Suggested semester for ${course.code}`"
                placeholder="—"
                @change="setSemester(course, $event)"
              />
              <span v-else class="text-small">{{ course.suggested_semester ?? '—' }}</span>
            </td>
            <td v-if="canManage" class="px-2 py-3 text-right">
              <IconButton :icon="Trash2" variant="danger" :label="`Remove ${course.code} from the curriculum`" @click="remove(course)" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <EmptyState v-else title="No courses in the curriculum" description="Add the courses students of this program take." />

    <form v-if="canManage" class="mt-6 grid gap-3 border-t border-border-default pt-5 dark:border-dark-border sm:grid-cols-[2fr_1fr_auto] sm:items-end" @submit.prevent="add">
      <BaseSelect v-model="form.course_id" label="Add a course" :options="courseOptions" placeholder="Select a course" :error="form.errors.course_id" />
      <BaseInput v-model="form.suggested_semester" name="suggested_semester" label="Semester" type="number" min="1" max="16" placeholder="Optional" :error="form.errors.suggested_semester" />
      <IconButton :icon="Plus" type="submit" size="md" variant="primary" label="Add course to curriculum" :loading="form.processing" :disabled="!form.course_id" />
      <label class="flex min-h-11 items-center gap-2 text-small text-ink dark:text-dark-ink sm:col-span-3">
        <input v-model="form.is_required" type="checkbox" class="h-4 w-4 rounded-sm border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
        Required for the degree
      </label>
    </form>
  </BaseCard>
</template>
