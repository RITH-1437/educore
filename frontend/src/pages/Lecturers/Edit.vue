<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import LecturerForm from '../../components/lecturers/LecturerForm.vue'

const props = defineProps({
  lecturer: { type: Object, required: true },
  faculties: { type: Object, required: true },
  departments: { type: Object, required: true },
  employmentTypes: { type: Array, default: () => [] },
})

const form = useForm({
  email: props.lecturer.user?.email ?? '',
  phone: props.lecturer.user?.phone ?? '',
  staff_number: props.lecturer.staff_number,
  first_name: props.lecturer.first_name,
  last_name: props.lecturer.last_name,
  title: props.lecturer.title ?? '',
  department_id: props.lecturer.department_id,
  position: props.lecturer.position ?? '',
  specialization: props.lecturer.specialization ?? '',
  employment_type: props.lecturer.employment_type,
})

// An archived department is not selectable (the API rejects it), so the
// current one is included explicitly to keep the field populated.
const departments = computed(() => {
  const list = props.departments?.data ?? []
  const current = props.lecturer.department
  return !current || list.some((department) => department.id === current.id)
    ? list
    : [...list, { id: current.id, code: current.code, name: current.name, faculty_id: current.faculty_id }]
})

const faculties = computed(() => props.faculties?.data ?? [])

const submit = () => form.put(`/lecturers/${props.lecturer.id}`, { preserveScroll: true })
const toggleActive = () =>
  router.post(`/lecturers/${props.lecturer.id}/${props.lecturer.is_active ? 'deactivate' : 'reactivate'}`, {}, { preserveScroll: true })
</script>

<template>
  <Head :title="`Edit ${lecturer.full_name} - EduCore`" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="People" :title="lecturer.full_name" :description="`${lecturer.staff_number} · ${lecturer.department?.name ?? ''}`">
      <template #actions>
        <BaseButton href="/lecturers" variant="secondary">Back to lecturers</BaseButton>
      </template>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-3">
      <StatusBadge :status="lecturer.is_active ? 'active' : 'inactive'" />
      <BaseButton size="sm" :variant="lecturer.is_active ? 'secondary' : 'success'" @click="toggleActive">
        {{ lecturer.is_active ? 'Deactivate' : 'Reactivate' }}
      </BaseButton>
    </div>

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

    <BaseCard padding="lg">
      <form class="space-y-6" @submit.prevent="submit">
        <LecturerForm :form="form" mode="edit" :faculties="faculties" :departments="departments" :employment-types="employmentTypes" />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <BaseButton href="/lecturers" variant="ghost">Cancel</BaseButton>
        </div>
      </form>
    </BaseCard>

    <BaseCard title="Teaching assignments" padding="lg">
      <template #description>Sections this lecturer teaches will appear here once Class / Section management (9.8) is available.</template>
    </BaseCard>
  </div>
</template>
