<script setup>
import { computed, ref, watch } from 'vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'

// Shared by the "New lecturer" modal and the edit page. The parent owns the
// Inertia form and the submit/cancel controls.
//   mode="create": choose between creating a new account or linking an
//                  existing Lecturer-role account without a profile.
//   mode="edit":   edit the linked account's email/phone alongside the profile.
const props = defineProps({
  form: { type: Object, required: true },
  mode: { type: String, default: 'create', validator: (value) => ['create', 'edit'].includes(value) },
  faculties: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  employmentTypes: { type: Array, default: () => [] },
  unlinkedAccounts: { type: Array, default: () => [] },
})

const accountMode = ref(props.form.user_id ? 'link' : 'new')

// The faculty only narrows the department list — it is not submitted.
const facultyId = ref(props.departments.find((department) => department.id === props.form.department_id)?.faculty_id ?? '')

const facultyOptions = computed(() => props.faculties.map((faculty) => ({ value: faculty.id, label: `${faculty.code} — ${faculty.name}` })))
const departmentOptions = computed(() =>
  props.departments
    .filter((department) => !facultyId.value || department.faculty_id === facultyId.value)
    .map((department) => ({ value: department.id, label: `${department.code} — ${department.name}` })),
)
const typeOptions = computed(() => props.employmentTypes.map((type) => ({ value: type, label: type.replace('_', ' ').replace(/^./, (c) => c.toUpperCase()) })))
const accountOptions = computed(() => props.unlinkedAccounts.map((account) => ({ value: account.id, label: `${account.name} — ${account.email}` })))

watch(facultyId, () => {
  const stillValid = props.departments.some((department) => department.id === props.form.department_id && (!facultyId.value || department.faculty_id === facultyId.value))
  if (!stillValid) props.form.department_id = ''
})

// Only one way of providing the account is submitted.
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
            <input v-model="accountMode" type="radio" value="new" class="h-4 w-4 accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
            Create a new account
          </label>
          <label class="flex min-h-11 items-center gap-2 text-small text-ink dark:text-dark-ink" :class="unlinkedAccounts.length ? '' : 'opacity-50'">
            <input v-model="accountMode" type="radio" value="link" :disabled="!unlinkedAccounts.length" class="h-4 w-4 accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
            Link an existing lecturer account ({{ unlinkedAccounts.length }})
          </label>
        </div>

        <BaseSelect v-if="accountMode === 'link'" v-model="form.user_id" label="Account" :options="accountOptions" placeholder="Select an account" :error="form.errors.user_id" required />

        <template v-else>
          <div class="grid gap-5 sm:grid-cols-2">
            <BaseInput v-model="form.email" name="email" label="Email" type="email" autocomplete="off" :error="form.errors.email" required />
            <BaseInput v-model="form.phone" name="phone" label="Phone" :error="form.errors.phone" />
          </div>
          <div class="grid gap-5 sm:grid-cols-2">
            <BaseInput v-model="form.password" name="password" label="Initial password" type="password" autocomplete="new-password" :error="form.errors.password" hint="At least 8 characters. Share it securely." required />
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
      <legend class="sr-only">Profile</legend>
      <p class="text-label font-semibold text-ink dark:text-dark-ink" aria-hidden="true">Profile</p>

      <div class="grid gap-5 sm:grid-cols-[1fr_2fr_2fr]">
        <BaseInput v-model="form.title" name="title" label="Title" placeholder="Dr." :error="form.errors.title" />
        <BaseInput v-model="form.first_name" name="first_name" label="First name" :error="form.errors.first_name" required />
        <BaseInput v-model="form.last_name" name="last_name" label="Last name" :error="form.errors.last_name" required />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.staff_number" name="staff_number" label="Staff number" placeholder="LEC-0001" :error="form.errors.staff_number" required />
        <BaseSelect v-model="form.employment_type" label="Employment type" :options="typeOptions" :error="form.errors.employment_type" />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseSelect v-model="facultyId" label="Faculty" :options="facultyOptions" placeholder="All faculties" />
        <BaseSelect v-model="form.department_id" label="Department" :options="departmentOptions" placeholder="Select a department" :error="form.errors.department_id" required />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.position" name="position" label="Position" placeholder="Senior Lecturer" :error="form.errors.position" />
        <BaseInput v-model="form.specialization" name="specialization" label="Specialization" placeholder="Distributed systems" :error="form.errors.specialization" />
      </div>
    </fieldset>
  </div>
</template>
