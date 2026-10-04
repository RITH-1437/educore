<script setup>
import { FileText, Pencil, Plus, Trash2 } from '@lucide/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseTable from '../../components/BaseTable.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import IconButton from '../../components/IconButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  types: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '' }) },
  canManage: { type: Boolean, default: false },
})

const editing = ref(null)
const showForm = ref(false)
const search = ref(props.filters.search ?? '')

const blank = {
  code: '',
  name: '',
  description: '',
  requires_fee: false,
  fee_amount: '0.00',
  is_active: true,
  sort_order: 0,
}

const form = useForm({ ...blank })

const rows = computed(() => props.types.data ?? [])

const open = (type = null) => {
  editing.value = type
  if (type) {
    form.defaults({
      code: type.code,
      name: type.name,
      description: type.description ?? '',
      requires_fee: Boolean(type.requires_fee),
      fee_amount: type.fee_amount ? Number(type.fee_amount).toFixed(2) : '0.00',
      is_active: Boolean(type.is_active),
      sort_order: type.sort_order ?? 0,
    }).reset()
  } else {
    form.defaults({ ...blank }).reset()
  }
  form.clearErrors()
  showForm.value = true
}

const save = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => (showForm.value = false),
  }

  const payload = {
    ...form.data(),
    fee_amount: form.requires_fee ? Number(form.fee_amount || 0) : 0,
    sort_order: Number(form.sort_order || 0),
  }

  if (editing.value) {
    form.transform(() => payload).put(`/document-types/${editing.value.id}`, options)
  } else {
    form.transform(() => payload).post('/document-types', options)
  }
}

const remove = (type) => {
  if (confirm(`Delete document type '${type.name}'?`)) {
    router.delete(`/document-types/${type.id}`, { preserveScroll: true })
  }
}

const applySearch = () => {
  router.get('/document-types', { search: search.value || undefined }, { preserveState: true, replace: true })
}

const columns = [
  { key: 'name', label: 'Document type' },
  { key: 'code', label: 'Code' },
  { key: 'fee', label: 'Fee' },
  { key: 'template', label: 'Template' },
  { key: 'requests', label: 'Requests', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Document types - EduCore" />
  <div class="space-y-6">
    <PageHeader
      eyebrow="Operations"
      title="Document types"
      description="Configure official document types, automated fees, and generation templates."
    >
      <template #actions>
        <IconButton :icon="FileText" href="/documents" size="md" label="Document requests" />
        <IconButton
          v-if="canManage"
          :icon="Plus"
          size="md"
          variant="primary"
          label="Add document type"
          @click="open()"
        />
      </template>
    </PageHeader>

    <div class="flex items-center justify-between gap-4">
      <div class="max-w-xs flex-1">
        <BaseInput
          v-model="search"
          name="search"
          placeholder="Search by code or name..."
          type="search"
          @keydown.enter="applySearch"
        />
      </div>
    </div>

    <BaseTable
      :columns="columns"
      :rows="rows"
      caption="Document types"
      empty-title="No document types found"
      empty-description="Add document types to allow students to submit requests."
    >
      <template #cell-name="{ row }">
        <p class="font-medium text-ink dark:text-dark-ink">{{ row.name }}</p>
        <p v-if="row.description" class="text-caption text-muted dark:text-dark-muted line-clamp-1">
          {{ row.description }}
        </p>
      </template>

      <template #cell-code="{ row }">
        <code class="rounded bg-background px-1.5 py-0.5 font-mono text-caption text-ink dark:bg-dark-surface-2 dark:text-dark-ink">
          {{ row.code }}
        </code>
      </template>

      <template #cell-fee="{ row }">
        <span
          v-if="row.requires_fee"
          class="inline-flex items-center rounded-full bg-warning/10 px-2 py-0.5 text-caption font-semibold text-warning-dark dark:text-warning"
        >
          ${{ Number(row.fee_amount).toFixed(2) }}
        </span>
        <span
          v-else
          class="inline-flex items-center rounded-full bg-success/10 px-2 py-0.5 text-caption font-semibold text-success-dark dark:text-success"
        >
          Free
        </span>
      </template>

      <template #cell-template="{ row }">
        <span
          class="inline-flex items-center rounded px-2 py-0.5 text-caption"
          :class="row.is_generatable ? 'bg-primary/10 text-primary dark:text-dark-primary' : 'bg-muted/10 text-muted'"
        >
          {{ row.is_generatable ? 'System template' : 'Manual' }}
        </span>
      </template>

      <template #cell-requests="{ row }">
        <span class="tabular-nums text-small text-ink dark:text-dark-ink">
          {{ row.requests_count ?? 0 }}
        </span>
      </template>

      <template #cell-status="{ row }">
        <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex justify-end gap-1">
          <IconButton
            v-if="canManage"
            :icon="Pencil"
            :label="`Edit ${row.name}`"
            size="sm"
            @click="open(row)"
          />
          <IconButton
            v-if="canManage && !row.requests_count"
            :icon="Trash2"
            variant="ghost"
            class="text-danger hover:bg-danger/10 hover:text-danger-dark"
            :label="`Delete ${row.name}`"
            size="sm"
            @click="remove(row)"
          />
        </div>
      </template>
    </BaseTable>

    <Pagination v-if="types.meta?.links" :links="types.meta.links" />

    <BaseModal v-model="showForm" :title="editing ? 'Edit document type' : 'Add document type'" size="md">
      <form id="document-type-form" class="space-y-4" @submit.prevent="save">
        <BaseInput
          v-model="form.code"
          name="code"
          label="Code"
          placeholder="e.g. transcript_rush"
          required
          :disabled="Boolean(editing)"
          :error="form.errors.code"
          hint="Lowercase alphanumeric with underscores. Permanent identifier."
        />

        <BaseInput
          v-model="form.name"
          name="name"
          label="Name"
          placeholder="e.g. Academic Transcript"
          required
          :error="form.errors.name"
        />

        <BaseTextarea
          v-model="form.description"
          name="description"
          label="Description"
          placeholder="Brief description shown to students when requesting..."
          :rows="2"
          :error="form.errors.description"
        />

        <div class="rounded-lg border border-border-default p-3 space-y-3 dark:border-dark-border">
          <label class="flex items-center gap-2 text-small font-medium text-ink dark:text-dark-ink cursor-pointer">
            <input
              v-model="form.requires_fee"
              type="checkbox"
              class="size-4 rounded border-border-default text-primary focus-visible:outline-2 focus-visible:outline-primary"
            />
            Requires fee payment
          </label>

          <div v-if="form.requires_fee" class="pl-6 space-y-2">
            <BaseInput
              v-model="form.fee_amount"
              name="fee_amount"
              label="Fee amount (USD)"
              type="number"
              step="0.01"
              min="0"
              required
              :error="form.errors.fee_amount"
              hint="Approving a request for this type will automatically generate an invoice."
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <BaseInput
            v-model="form.sort_order"
            name="sort_order"
            label="Sort order"
            type="number"
            min="0"
            :error="form.errors.sort_order"
          />

          <div class="flex items-center pt-6">
            <label class="flex items-center gap-2 text-small text-ink dark:text-dark-ink cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="size-4 rounded border-border-default text-primary focus-visible:outline-2 focus-visible:outline-primary"
              />
              Active (open for requests)
            </label>
          </div>
        </div>
      </form>

      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
          <BaseButton type="submit" form="document-type-form" :loading="form.processing">
            {{ editing ? 'Save changes' : 'Create type' }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
