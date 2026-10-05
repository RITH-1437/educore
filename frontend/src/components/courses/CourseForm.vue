<script setup>
import { computed } from 'vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseTextarea from '../BaseTextarea.vue'

// Shared by the "New course" modal and the edit page. The parent owns the
// Inertia form (`form`) and the submit/cancel controls.
const props = defineProps({
  form: { type: Object, required: true },
  departments: { type: Array, default: () => [] },
  levels: { type: Array, default: () => [] },
  /** Statuses an editor may pick (archiving has its own action). */
  statuses: { type: Array, default: () => ['draft', 'active'] },
  /** Archived courses keep their status; the field is disabled for them. */
  statusLocked: { type: Boolean, default: false },
})

const titleCase = (value) => value.charAt(0).toUpperCase() + value.slice(1)

const departmentOptions = computed(() =>
  props.departments.map((department) => ({ value: department.id, label: `${department.code} — ${department.name}` })),
)
const levelOptions = computed(() => props.levels.map((level) => ({ value: level, label: titleCase(level) })))
const statusOptions = computed(() => props.statuses.map((status) => ({ value: status, label: titleCase(status) })))
</script>

<template>
  <div class="space-y-5">
    <BaseSelect v-model="form.department_id" label="Department" :options="departmentOptions" placeholder="Select a department" :error="form.errors.department_id" required />

    <div class="grid gap-5 sm:grid-cols-2">
      <BaseInput v-model="form.code" name="code" label="Code" placeholder="CS201" :error="form.errors.code" required />
      <BaseSelect v-model="form.course_level" label="Level" :options="levelOptions" placeholder="Not set" :error="form.errors.course_level" />
    </div>

    <BaseInput v-model="form.name" name="name" label="Name" placeholder="Data Structures" :error="form.errors.name" required />

    <div class="grid gap-5 sm:grid-cols-3">
      <BaseInput v-model="form.credits" name="credits" label="Credits" type="number" min="0.5" max="99.99" step="0.5" placeholder="3" :error="form.errors.credits" required />
      <BaseInput v-model="form.lecture_hours" name="lecture_hours" label="Lecture hours" type="number" min="0" placeholder="30" :error="form.errors.lecture_hours" />
      <BaseInput v-model="form.lab_hours" name="lab_hours" label="Lab hours" type="number" min="0" placeholder="15" :error="form.errors.lab_hours" />
    </div>

    <BaseTextarea v-model="form.description" label="Description" rows="3" :error="form.errors.description" />

    <BaseSelect
      v-model="form.status"
      label="Status"
      :options="statusOptions"
      :disabled="statusLocked"
      :error="form.errors.status"
    >
      <template #hint>{{ statusLocked ? 'Archived courses are brought back with Reactivate.' : 'Draft courses are not offered yet; active courses are ready to use.' }}</template>
    </BaseSelect>
  </div>
</template>
