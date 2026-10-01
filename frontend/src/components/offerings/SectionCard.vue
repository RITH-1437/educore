<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { X } from '@lucide/vue'
import BaseBadge from '../BaseBadge.vue'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import StatusBadge from '../StatusBadge.vue'
import SectionSchedule from './SectionSchedule.vue'
import { useConfirm } from '../../composables/useConfirm'

// One section: capacity/status edit, assigned lecturers, assign form.
const props = defineProps({
  section: { type: Object, required: true },
  lecturers: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
  rooms: { type: Array, default: () => [] },
  days: { type: Object, default: () => ({}) },
  canManage: { type: Boolean, default: false },
})

const { confirm } = useConfirm()
const editing = ref(false)
const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)

const edit = useForm({ code: props.section.code, name: props.section.name ?? '', capacity: props.section.capacity, status: props.section.status })
const assign = useForm({ lecturer_id: '', role: props.section.lecturers?.some((l) => l.role === 'primary') ? 'assistant' : 'primary' })

const assigned = computed(() => new Set((props.section.lecturers ?? []).map((lecturer) => lecturer.id)))
const lecturerOptions = computed(() => props.lecturers.filter((lecturer) => !assigned.value.has(lecturer.id)).map((lecturer) => ({ value: lecturer.id, label: lecturer.label })))
const statusOptions = computed(() => props.statuses.map((value) => ({ value, label: label(value) })))
const roleOptions = computed(() => props.roles.map((value) => ({ value, label: label(value) })))
const fill = computed(() => (props.section.capacity ? Math.min(100, Math.round(((props.section.enrolled ?? 0) / props.section.capacity) * 100)) : 0))

const save = () => edit.put(`/sections/${props.section.id}`, { preserveScroll: true, onSuccess: () => { editing.value = false } })
const addLecturer = () => assign.post(`/sections/${props.section.id}/lecturers`, { preserveScroll: true, onSuccess: () => assign.reset('lecturer_id') })
const removeLecturer = (lecturer) => router.delete(`/sections/${props.section.id}/lecturers/${lecturer.id}`, { preserveScroll: true })
const destroy = async () => {
  if (await confirm({ title: `Delete section ${props.section.code}?`, message: 'Refused once it has enrollments, attendance, assignments, exams or a schedule. Lecturer assignments are removed with it.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/sections/${props.section.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <BaseCard padding="md">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-h4 font-semibold text-ink dark:text-dark-ink">Section {{ section.code }} <span v-if="section.name" class="text-small font-normal text-muted dark:text-dark-muted">· {{ section.name }}</span></h3>
        <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ section.enrolled ?? 0 }} / {{ section.capacity }} seats taken</p>
      </div>
      <StatusBadge :status="section.status" />
    </div>
    <div class="mt-3 h-2 overflow-hidden rounded-pill bg-background dark:bg-dark-surface-2" role="presentation">
      <div class="h-full rounded-pill bg-primary dark:bg-dark-primary" :style="{ width: `${fill}%` }" />
    </div>

    <form v-if="editing" class="mt-4 grid gap-3 sm:grid-cols-4 sm:items-end" @submit.prevent="save">
      <BaseInput v-model="edit.code" name="code" label="Code" :error="edit.errors.code" />
      <BaseInput v-model="edit.name" name="name" label="Name" :error="edit.errors.name" />
      <BaseInput v-model="edit.capacity" name="capacity" label="Capacity" type="number" min="1" :error="edit.errors.capacity" />
      <BaseSelect v-model="edit.status" label="Status" :options="statusOptions" :error="edit.errors.status" />
      <div class="flex gap-2 sm:col-span-4">
        <BaseButton type="submit" size="sm" :loading="edit.processing">Save</BaseButton>
        <BaseButton size="sm" variant="ghost" @click="editing = false">Cancel</BaseButton>
      </div>
    </form>

    <SectionSchedule class="mt-5" :section="section" :rooms="rooms" :days="days" :can-manage="canManage" />

    <h4 class="mt-5 text-small font-semibold text-ink dark:text-dark-ink">Lecturers</h4>
    <ul v-if="section.lecturers?.length" class="mt-2 space-y-2">
      <li v-for="lecturer in section.lecturers" :key="lecturer.id" class="flex items-center justify-between gap-2 text-small">
        <span class="flex items-center gap-2 text-ink dark:text-dark-ink">
          {{ lecturer.full_name }}
          <BaseBadge :variant="lecturer.role === 'primary' ? 'primary' : 'muted'" size="sm">{{ label(lecturer.role) }}</BaseBadge>
        </span>
        <button v-if="canManage" type="button" class="inline-flex min-h-9 items-center gap-1 rounded-md px-2 text-error hover:bg-error/5 focus-visible:outline-2 focus-visible:outline-error dark:text-red-300" :aria-label="`Remove ${lecturer.full_name}`" @click="removeLecturer(lecturer)">
          <X class="h-4 w-4" aria-hidden="true" />
        </button>
      </li>
    </ul>
    <p v-else class="mt-2 text-small text-muted dark:text-dark-muted">No lecturer assigned yet.</p>

    <form v-if="canManage" class="mt-4 grid gap-3 border-t border-border-default pt-4 dark:border-dark-border sm:grid-cols-[2fr_1fr_auto] sm:items-end" @submit.prevent="addLecturer">
      <BaseSelect v-model="assign.lecturer_id" label="Assign lecturer" :options="lecturerOptions" placeholder="Select an active lecturer" :error="assign.errors.lecturer_id" />
      <BaseSelect v-model="assign.role" label="Role" :options="roleOptions" :error="assign.errors.role" />
      <BaseButton type="submit" size="md" :disabled="!assign.lecturer_id" :loading="assign.processing">Assign</BaseButton>
    </form>

    <div class="mt-4">
      <BaseButton :href="`/attendance/sections/${section.id}`" size="sm" variant="ghost">Attendance register</BaseButton>
    </div>

    <div v-if="canManage" class="mt-4 flex gap-3">
      <BaseButton v-if="!editing" size="sm" variant="secondary" @click="editing = true">Edit section</BaseButton>
      <BaseButton size="sm" variant="ghost" class="text-error dark:text-red-300" @click="destroy">Delete</BaseButton>
    </div>
  </BaseCard>
</template>
