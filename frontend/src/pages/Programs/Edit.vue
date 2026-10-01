<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import ProgramCurriculum from '../../components/programs/ProgramCurriculum.vue'
import ProgramForm from '../../components/programs/ProgramForm.vue'

const props = defineProps({
  program: { type: Object, required: true },
  faculties: { type: Object, required: true },
  departments: { type: Object, required: true },
  degreeLevels: { type: Array, default: () => [] },
  availableCourses: { type: Array, default: () => [] },
})

const form = useForm({
  department_id: props.program.department_id,
  code: props.program.code,
  name: props.program.name,
  degree_level: props.program.degree_level,
  duration_years: props.program.duration_years ?? '',
  credits_required: props.program.credits_required ?? '',
})

// An archived department is not selectable (the API rejects it), so the
// current one is included explicitly to keep the field populated.
const departments = computed(() => {
  const list = props.departments?.data ?? []
  const current = props.program.department
  return list.some((department) => department.id === current?.id) || !current
    ? list
    : [...list, { id: current.id, code: current.code, name: current.name, faculty_id: current.faculty_id }]
})

const faculties = computed(() => props.faculties?.data ?? [])

const submit = () => form.put(`/programs/${props.program.id}`, { preserveScroll: true })
</script>

<template>
  <Head :title="`Edit ${program.code} - EduCore`" />
  <div class="mx-auto max-w-3xl space-y-6">
    <PageHeader eyebrow="Academic structure" :title="program.name" description="Changing the department moves the program; students and curriculum keep pointing at the same program.">
      <template #actions>
        <Link href="/programs" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Back to programs</Link>
      </template>
    </PageHeader>

    <div class="flex items-center gap-3">
      <StatusBadge :status="program.is_active ? 'active' : 'archived'" />
      <span class="text-small text-muted dark:text-dark-muted">{{ program.department?.name }}</span>
    </div>

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

    <BaseCard padding="lg">
      <form class="space-y-5" @submit.prevent="submit">
        <ProgramForm :form="form" :faculties="faculties" :departments="departments" :degree-levels="degreeLevels" />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <BaseButton href="/programs" variant="ghost">Cancel</BaseButton>
        </div>
      </form>
    </BaseCard>

    <ProgramCurriculum :program="program" :available-courses="availableCourses" can-manage />
  </div>
</template>
