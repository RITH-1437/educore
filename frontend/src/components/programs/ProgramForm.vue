<script setup>
import { computed } from 'vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'

// Shared by the "New program" modal and the edit page. The parent owns the
// Inertia form (`form`) and the submit/cancel controls.
const props = defineProps({
  form: { type: Object, required: true },
  departments: { type: Array, default: () => [] },
  degreeLevels: { type: Array, default: () => [] },
})

const departmentOptions = computed(() =>
  props.departments.map((department) => ({ value: department.id, label: `${department.code} — ${department.name}` })),
)

const levelOptions = computed(() => props.degreeLevels.map((level) => ({ value: level, label: level.charAt(0).toUpperCase() + level.slice(1) })))
</script>

<template>
  <div class="space-y-5">
    <BaseSelect v-model="form.department_id" label="Department" :options="departmentOptions" placeholder="Select a department" :error="form.errors.department_id" required />

    <div class="grid gap-5 sm:grid-cols-2">
      <BaseInput v-model="form.code" name="code" label="Code" placeholder="BSCS" :error="form.errors.code" required />
      <BaseSelect v-model="form.degree_level" label="Degree level" :options="levelOptions" placeholder="Select a level" :error="form.errors.degree_level" required />
    </div>

    <BaseInput v-model="form.name" name="name" label="Name" placeholder="Bachelor of Computer Science" :error="form.errors.name" required />

    <div class="grid gap-5 sm:grid-cols-2">
      <BaseInput v-model="form.duration_years" name="duration_years" label="Duration (years)" type="number" min="1" max="10" placeholder="4" :error="form.errors.duration_years" />
      <BaseInput v-model="form.credits_required" name="credits_required" label="Credits required" type="number" min="0" step="0.5" placeholder="144" :error="form.errors.credits_required" />
    </div>
  </div>
</template>
