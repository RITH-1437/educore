<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArrowLeft, UserCheck, UserX } from '@lucide/vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import LecturerForm from '../../components/lecturers/LecturerForm.vue'

const props = defineProps({
  lecturer: { type: Object, required: true },
  departments: { type: Object, required: true },
  employmentTypes: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
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
    : [...list, { id: current.id, code: current.code, name: current.name }]
})


const submit = () => form.put(`/lecturers/${props.lecturer.id}`, { preserveScroll: true })
const toggleActive = () =>
  router.post(`/lecturers/${props.lecturer.id}/${props.lecturer.is_active ? 'deactivate' : 'reactivate'}`, {}, { preserveScroll: true })
</script>

<template>
  <Head :title="`Edit ${lecturer.full_name} - EduCore`" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="People" :title="lecturer.full_name" :description="`${lecturer.staff_number} · ${lecturer.department?.name ?? ''}`">
      <template #actions>
        <IconButton :icon="ArrowLeft" href="/lecturers" size="md" label="Back to lecturers" />
      </template>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-3">
      <StatusBadge :status="lecturer.is_active ? 'active' : 'inactive'" />
      <IconButton :icon="lecturer.is_active ? UserX : UserCheck" :variant="lecturer.is_active ? 'default' : 'success'" :label="lecturer.is_active ? 'Deactivate lecturer' : 'Reactivate lecturer'" @click="toggleActive" />
    </div>

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

    <BaseCard padding="lg">
      <form class="space-y-6" @submit.prevent="submit">
        <LecturerForm :form="form" mode="edit" :departments="departments" :employment-types="employmentTypes" />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <BaseButton href="/lecturers" variant="ghost">Cancel</BaseButton>
        </div>
      </form>
    </BaseCard>

    <BaseCard title="Teaching assignments" padding="lg">
      <template #description>Sections this lecturer is assigned to. Assign lecturers from the offering page.</template>
      <ul v-if="sections.length" class="divide-y divide-border-default dark:divide-dark-border">
        <li v-for="section in sections" :key="section.id" class="py-1">
          <Link :href="`/offerings/${section.offering?.id}`" class="-mx-3 flex flex-wrap items-center justify-between gap-3 rounded-md px-3 py-3 transition-colors duration-150 hover:bg-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:hover:bg-dark-surface-2">
            <div>
              <p class="text-small font-medium text-ink dark:text-dark-ink">
                <span class="font-semibold">{{ section.offering?.course?.code }}</span> · Section {{ section.code }}
              </p>
              <p class="text-caption text-muted dark:text-dark-muted">{{ section.offering?.semester?.academic_year }} · {{ section.offering?.semester?.name }} · {{ section.capacity }} seats</p>
            </div>
          </Link>
        </li>
      </ul>
      <p v-else class="text-small text-muted dark:text-dark-muted">Not assigned to any section yet.</p>
    </BaseCard>
  </div>
</template>
