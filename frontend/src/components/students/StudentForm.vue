<script setup>
import { computed, ref, watch } from 'vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseTextarea from '../BaseTextarea.vue'

// Shared by the "New student" modal and the edit page.
//   mode="create": account (new or link existing) + first program.
//   mode="edit":   account email/phone + profile (program/status have own panels).
const props = defineProps({
  form: { type: Object, required: true },
  mode: { type: String, default: 'create', validator: (value) => ['create', 'edit'].includes(value) },
  departments: { type: Array, default: () => [] },
  programs: { type: Array, default: () => [] },
  genders: { type: Array, default: () => [] },
  unlinkedAccounts: { type: Array, default: () => [] },
})

const accountMode = ref(props.form.user_id ? 'link' : 'new')
// The department only narrows the program list — it is not submitted.
const departmentId = ref('')

const departmentOptions = computed(() => props.departments.map((department) => ({ value: department.id, label: `${department.code} — ${department.name}` })))
const programOptions = computed(() =>
  props.programs
    .filter((program) => !departmentId.value || program.department_id === departmentId.value)
    .map((program) => ({ value: program.id, label: `${program.code} — ${program.name}` })),
)
const genderOptions = computed(() => props.genders.map((gender) => ({ value: gender, label: gender.charAt(0).toUpperCase() + gender.slice(1) })))
const accountOptions = computed(() => props.unlinkedAccounts.map((account) => ({ value: account.id, label: `${account.name} — ${account.email}` })))

// Picking another department clears a program that no longer belongs to it.
watch(departmentId, () => {
  const stillValid = props.programs.some((program) => program.id === props.form.program_id && (!departmentId.value || program.department_id === departmentId.value))
  if (!stillValid) props.form.program_id = ''
})

watch(accountMode, (value) => {
  if (value === 'link') {
    props.form.email = ''
    props.form.password = ''
    props.form.password_confirmation = ''
  } else {
    props.form.user_id = ''
  }
})
</script>

<template>
  <div class="space-y-6">
    <fieldset class="space-y-4">
      <legend class="text-label font-semibold text-ink dark:text-dark-ink">Login account</legend>

      <template v-if="mode === 'create'">
        <div class="flex flex-wrap gap-4" role="radiogroup" aria-label="Account">
          <label class="flex min-h-11 items-center gap-2 text-small text-ink dark:text-dark-ink">
            <input v-model="accountMode" type="radio" value="new" class="h-4 w-4 accent-primary" /> Create a new account
          </label>
          <label class="flex min-h-11 items-center gap-2 text-small text-ink dark:text-dark-ink" :class="unlinkedAccounts.length ? '' : 'opacity-50'">
            <input v-model="accountMode" type="radio" value="link" :disabled="!unlinkedAccounts.length" class="h-4 w-4 accent-primary" />
            Link an existing student account ({{ unlinkedAccounts.length }})
          </label>
        </div>
        <BaseSelect v-if="accountMode === 'link'" v-model="form.user_id" label="Account" :options="accountOptions" placeholder="Select an account" :error="form.errors.user_id" required />
        <template v-else>
          <div class="grid gap-5 sm:grid-cols-2">
            <BaseInput v-model="form.email" name="email" label="Email" type="email" autocomplete="off" :error="form.errors.email" required />
            <BaseInput v-model="form.phone" name="phone" label="Phone" :error="form.errors.phone" />
          </div>
          <div class="grid gap-5 sm:grid-cols-2">
            <BaseInput v-model="form.password" name="password" label="Initial password" type="password" autocomplete="new-password" hint="At least 8 characters." :error="form.errors.password" required />
            <BaseInput v-model="form.password_confirmation" name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
          </div>
        </template>
      </template>

      <div v-else class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.email" name="email" label="Email" type="email" :error="form.errors.email" required />
        <BaseInput v-model="form.phone" name="phone" label="Phone" :error="form.errors.phone" />
      </div>
    </fieldset>

    <fieldset class="space-y-4 border-t border-border-default pt-5 dark:border-dark-border">
      <legend class="text-label font-semibold text-ink dark:text-dark-ink">Profile</legend>
      <div class="grid gap-5 sm:grid-cols-3">
        <BaseInput v-model="form.student_number" name="student_number" label="Student ID" placeholder="ITC-2026-0001" :error="form.errors.student_number" required />
        <BaseInput v-model="form.first_name" name="first_name" label="First name" :error="form.errors.first_name" required />
        <BaseInput v-model="form.last_name" name="last_name" label="Last name" :error="form.errors.last_name" required />
      </div>
      <div class="grid gap-5 sm:grid-cols-3">
        <BaseSelect v-model="form.gender" label="Gender" :options="genderOptions" placeholder="Not set" :error="form.errors.gender" />
        <BaseInput v-model="form.date_of_birth" name="date_of_birth" label="Date of birth" type="date" :error="form.errors.date_of_birth" />
        <BaseInput v-model="form.national_id" name="national_id" label="National ID" :error="form.errors.national_id" />
      </div>
      <BaseTextarea v-model="form.address" label="Address" rows="2" :error="form.errors.address" />
      <div class="grid gap-5 sm:grid-cols-3">
        <BaseInput v-model="form.emergency_contact_name" name="emergency_contact_name" label="Emergency contact" :error="form.errors.emergency_contact_name" />
        <BaseInput v-model="form.emergency_contact_phone" name="emergency_contact_phone" label="Emergency phone" :error="form.errors.emergency_contact_phone" />
        <BaseInput v-model="form.enrollment_date" name="enrollment_date" label="Enrollment date" type="date" :error="form.errors.enrollment_date" />
      </div>
    </fieldset>

    <fieldset v-if="mode === 'create'" class="space-y-4 border-t border-border-default pt-5 dark:border-dark-border">
      <legend class="text-label font-semibold text-ink dark:text-dark-ink">Program</legend>
      <div class="grid gap-5 sm:grid-cols-2">
        <BaseSelect v-model="departmentId" label="Department" :options="departmentOptions" placeholder="All departments" />
        <BaseSelect v-model="form.program_id" label="Program" :options="programOptions" placeholder="Select a program" :error="form.errors.program_id" required />
      </div>
    </fieldset>
  </div>
</template>
