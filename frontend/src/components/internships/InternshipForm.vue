<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseTextarea from '../BaseTextarea.vue'

// Application fields, shared by the student portal (create) and the
// internship page (edit). Posts or puts itself; emits `saved` on success.
const props = defineProps({
  internship: { type: Object, default: null },
  companies: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Save' },
})
const emit = defineEmits(['saved'])

const form = useForm({
  company_id: props.internship?.company?.id ?? '',
  position_title: props.internship?.position_title ?? '',
  description: props.internship?.description ?? '',
  start_date: props.internship?.start_date ?? '',
  end_date: props.internship?.end_date ?? '',
  supervisor_name: props.internship?.supervisor_name ?? '',
  supervisor_email: props.internship?.supervisor_email ?? '',
  supervisor_phone: props.internship?.supervisor_phone ?? '',
})

// Keep a retired company selectable on an existing internship.
const companyOptions = computed(() => {
  const options = props.companies.map((c) => ({ value: c.id, label: c.industry ? `${c.name} · ${c.industry}` : c.name }))
  const current = props.internship?.company
  return current && !options.some((o) => o.value === current.id) ? [...options, { value: current.id, label: `${current.name} (inactive)` }] : options
})

const save = () => {
  const options = { preserveScroll: true, onSuccess: () => emit('saved') }
  props.internship ? form.put(`/internships/${props.internship.id}`, options) : form.post('/my-internships', options)
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="save">
    <div class="grid gap-4 sm:grid-cols-2">
      <BaseSelect v-model="form.company_id" :options="companyOptions" label="Company" placeholder="Choose a company" :error="form.errors.company_id" />
      <BaseInput v-model="form.position_title" name="position_title" label="Position" required :error="form.errors.position_title" />
      <BaseInput v-model="form.start_date" name="start_date" label="Start date" type="date" required :error="form.errors.start_date" />
      <BaseInput v-model="form.end_date" name="end_date" label="End date" type="date" required :error="form.errors.end_date" />
    </div>
    <BaseTextarea v-model="form.description" name="description" label="Role description (optional)" :rows="3" :error="form.errors.description" />
    <div class="grid gap-4 sm:grid-cols-3">
      <BaseInput v-model="form.supervisor_name" name="supervisor_name" label="Company supervisor" required :error="form.errors.supervisor_name" />
      <BaseInput v-model="form.supervisor_email" name="supervisor_email" label="Supervisor email" type="email" :error="form.errors.supervisor_email" />
      <BaseInput v-model="form.supervisor_phone" name="supervisor_phone" label="Supervisor phone" :error="form.errors.supervisor_phone" />
    </div>
    <p v-if="!companies.length" class="text-small text-warning">No host companies are open yet — ask the university office to add yours.</p>
    <div class="flex justify-end">
      <BaseButton type="submit" :loading="form.processing" :disabled="!companies.length && !internship">{{ submitLabel }}</BaseButton>
    </div>
  </form>
</template>
