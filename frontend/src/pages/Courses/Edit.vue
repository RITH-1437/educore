<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArchiveRestore, ArrowLeft } from '@lucide/vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import CourseForm from '../../components/courses/CourseForm.vue'
import GradingWeights from '../../components/courses/GradingWeights.vue'
import PrerequisiteEditor from '../../components/courses/PrerequisiteEditor.vue'

const props = defineProps({
  course: { type: Object, required: true },
  prerequisiteOptions: { type: Array, default: () => [] },
  gradingConfig: { type: Object, default: null },
  departments: { type: Object, required: true },
  programs: { type: Array, default: () => [] },
  levels: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})

const form = useForm({
  department_id: props.course.department_id,
  code: props.course.code,
  name: props.course.name,
  credits: props.course.credits,
  lecture_hours: props.course.lecture_hours ?? '',
  lab_hours: props.course.lab_hours ?? '',
  description: props.course.description ?? '',
  course_level: props.course.course_level ?? '',
  status: props.course.status === 'archived' ? 'active' : props.course.status,
})

const archived = computed(() => props.course.status === 'archived')

// An archived department is not selectable (the API rejects it), so the
// current one is included explicitly to keep the field populated.
const departments = computed(() => {
  const list = props.departments?.data ?? []
  const current = props.course.department
  return !current || list.some((department) => department.id === current.id)
    ? list
    : [...list, { id: current.id, code: current.code, name: current.name }]
})


const submit = () => form.put(`/courses/${props.course.id}`, { preserveScroll: true })
const reactivate = () => router.post(`/courses/${props.course.id}/reactivate`, {}, { preserveScroll: true })
</script>

<template>
  <Head :title="`Edit ${course.code} - EduCore`" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="Academics" :title="`${course.code} — ${course.name}`" description="Edit the course, manage its prerequisites and see where it is used.">
      <template #actions>
        <IconButton :icon="ArrowLeft" href="/courses" size="md" label="Back to courses" />
      </template>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-3">
      <StatusBadge :status="course.status" />
      <span class="text-small text-muted dark:text-dark-muted">{{ course.department?.name }}</span>
      <IconButton v-if="archived" :icon="ArchiveRestore" variant="success" label="Reactivate course" @click="reactivate" />
    </div>

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

    <BaseCard padding="lg">
      <form class="space-y-5" @submit.prevent="submit">
        <CourseForm :form="form" :departments="departments" :levels="levels" :status-locked="archived" />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <BaseButton href="/courses" variant="ghost">Cancel</BaseButton>
        </div>
      </form>
    </BaseCard>

    <PrerequisiteEditor :course="course" :options="prerequisiteOptions" can-manage />

    <GradingWeights v-if="gradingConfig" :course-id="course.id" :config="gradingConfig" />

    <BaseCard title="Used in programs" padding="lg">
      <template #description>Programs whose curriculum includes this course. Manage membership from the program page.</template>
      <ul v-if="course.programs?.length" class="divide-y divide-border-default dark:divide-dark-border">
        <li v-for="program in course.programs" :key="program.id" class="py-1">
          <Link :href="`/programs/${program.id}/edit`" class="-mx-3 flex flex-wrap items-center justify-between gap-3 rounded-md px-3 py-3 transition-colors duration-150 hover:bg-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:hover:bg-dark-surface-2">
            <div>
              <p class="text-small font-medium text-ink dark:text-dark-ink"><span class="font-semibold">{{ program.code }}</span> — {{ program.name }}</p>
              <p class="text-caption text-muted dark:text-dark-muted">
                {{ program.is_required ? 'Required' : 'Elective' }}<template v-if="program.suggested_semester"> · suggested semester {{ program.suggested_semester }}</template>
              </p>
            </div>
          </Link>
        </li>
      </ul>
      <EmptyState v-else title="Not in any curriculum" description="Add it to a program from the program's curriculum section." />
    </BaseCard>
  </div>
</template>
