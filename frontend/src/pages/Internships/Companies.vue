<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseTable from '../../components/BaseTable.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'

defineProps({
  companies: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
})

const editing = ref(null)
const showForm = ref(false)
const blank = { name: '', industry: '', contact_name: '', contact_email: '', contact_phone: '', address: '', website: '', is_active: true }
const form = useForm({ ...blank })

const open = (company = null) => {
  editing.value = company
  form.defaults(company ? { ...blank, ...Object.fromEntries(Object.keys(blank).map((k) => [k, company[k] ?? blank[k]])) } : { ...blank }).reset()
  form.clearErrors()
  showForm.value = true
}
const save = () => {
  const options = { preserveScroll: true, onSuccess: () => (showForm.value = false) }
  const request = form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
  editing.value ? request.put(`/internship-companies/${editing.value.id}`, options) : request.post('/internship-companies', options)
}

const columns = [
  { key: 'name', label: 'Company' },
  { key: 'contact', label: 'Contact' },
  { key: 'count', label: 'Internships', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Internship companies - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Operations" title="Internship companies" description="Host companies students can choose when applying.">
      <template #actions>
        <BaseButton href="/internships" variant="secondary">Internships</BaseButton>
        <BaseButton v-if="canManage" @click="open()">Add company</BaseButton>
      </template>
    </PageHeader>

    <BaseTable :columns="columns" :rows="companies" caption="Companies" empty-title="No companies yet" empty-description="Add the companies that host your students.">
      <template #cell-name="{ row }">
        <p class="font-medium">{{ row.name }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.industry ?? '' }}</p>
      </template>
      <template #cell-contact="{ row }">
        <p>{{ row.contact_name ?? '—' }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ [row.contact_email, row.contact_phone].filter(Boolean).join(' · ') }}</p>
      </template>
      <template #cell-count="{ row }"><span class="tabular-nums">{{ row.internships_count }}</span></template>
      <template #cell-status="{ row }"><StatusBadge :status="row.is_active ? 'active' : 'inactive'" /></template>
      <template #cell-actions="{ row }"><BaseButton v-if="canManage" size="sm" variant="secondary" @click="open(row)">Edit</BaseButton></template>
    </BaseTable>

    <BaseModal v-model="showForm" :title="editing ? 'Edit company' : 'Add company'" size="lg">
      <form id="company-form" class="space-y-4" @submit.prevent="save">
        <div class="grid gap-4 sm:grid-cols-2">
          <BaseInput v-model="form.name" name="name" label="Name" required :error="form.errors.name" />
          <BaseInput v-model="form.industry" name="industry" label="Industry" :error="form.errors.industry" />
          <BaseInput v-model="form.contact_name" name="contact_name" label="Contact person" :error="form.errors.contact_name" />
          <BaseInput v-model="form.contact_email" name="contact_email" label="Contact email" type="email" :error="form.errors.contact_email" />
          <BaseInput v-model="form.contact_phone" name="contact_phone" label="Contact phone" :error="form.errors.contact_phone" />
          <BaseInput v-model="form.website" name="website" label="Website" placeholder="https://" :error="form.errors.website" />
        </div>
        <BaseTextarea v-model="form.address" name="address" label="Address" :rows="2" :error="form.errors.address" />
        <label class="flex items-center gap-2 text-small text-ink dark:text-dark-ink">
          <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-border-default text-primary focus-visible:outline-2 focus-visible:outline-primary" />
          Open for new applications
        </label>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
          <BaseButton type="submit" form="company-form" :loading="form.processing">{{ editing ? 'Save changes' : 'Add company' }}</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
