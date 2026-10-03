<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArrowLeft, Trash2 } from '@lucide/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StudentForm from '../../components/students/StudentForm.vue'
import StudentLifecycle from '../../components/students/StudentLifecycle.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  student: { type: Object, required: true },
  faculties: { type: Object, required: true },
  programs: { type: Array, default: () => [] },
  genders: { type: Array, default: () => [] },
})

const { confirm } = useConfirm()
const s = props.student
const form = useForm({
  email: s.user?.email ?? '', phone: s.user?.phone ?? '',
  student_number: s.student_number, first_name: s.first_name, last_name: s.last_name,
  gender: s.gender ?? '', date_of_birth: s.date_of_birth ?? '', national_id: s.national_id ?? '',
  address: s.address ?? '', emergency_contact_name: s.emergency_contact_name ?? '',
  emergency_contact_phone: s.emergency_contact_phone ?? '', enrollment_date: s.enrollment_date ?? '',
})

const faculties = computed(() => props.faculties?.data ?? [])
const submit = () => form.put(`/students/${props.student.id}`, { preserveScroll: true })

const destroy = async () => {
  if (await confirm({ title: 'Delete student profile?', message: 'Only for profiles created by mistake. Refused once the student has enrollments, GPA, documents, invoices or internships. The account is kept but cannot sign in.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/students/${props.student.id}`)
  }
}
</script>

<template>
  <Head :title="`${student.full_name} - EduCore`" />
  <div class="mx-auto max-w-5xl space-y-6">
    <PageHeader eyebrow="People" :title="student.full_name" :description="`${student.student_number} · ${student.current_program?.program?.name ?? 'No active program'}`">
      <template #actions>
        <IconButton :icon="ArrowLeft" href="/students" size="md" label="Back to students" />
      </template>
    </PageHeader>

    <StudentLifecycle :student="student" :programs="programs" />

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

    <BaseCard title="Profile" padding="lg">
      <form class="space-y-6" @submit.prevent="submit">
        <StudentForm :form="form" mode="edit" :faculties="faculties" :programs="programs" :genders="genders" />
        <div class="flex flex-wrap items-center justify-between gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <IconButton :icon="Trash2" size="md" variant="danger" label="Delete profile" @click="destroy" />
        </div>
      </form>
    </BaseCard>
  </div>
</template>
