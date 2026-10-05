<script setup>
import IconButton from '../../components/IconButton.vue'
import { Calculator, Download, Plus } from '@lucide/vue'
import { exportUrl } from '../../utils/exports'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useLiveFilters } from '../../composables/useLiveFilters'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { invoiceBadge, money } from '../../utils/finance'

const props = defineProps({
  invoices: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  statuses: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  semesters: { type: Array, default: () => [] },
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const statusOptions = computed(() => [{ value: '', label: 'All statuses' }, ...props.statuses.map((value) => ({ value, label: invoiceBadge(value).label }))])
// Exports what the list currently shows (the applied filters, not unsaved input).
const csvUrl = computed(() => exportUrl('/invoices/export', { search: props.filters.search, filters: { status: props.filters.status } }))
const apply = (options = {}) => router.get('/invoices', { search: search.value || undefined, filters: status.value ? { status: status.value } : undefined }, { preserveState: true, replace: true, ...options })

const showTuitionModal = ref(false)
const tuitionForm = useForm({
  semester_id: props.semesters.find((s) => s.status === 'open' || s.status === 'active')?.id ?? props.semesters[0]?.id ?? '',
  due_date: '',
  rate_per_credit: '',
  dry_run: false,
})

const semesterOptions = computed(() =>
  props.semesters.map((s) => ({
    value: s.id,
    label: `${s.name} (${s.code})${s.status ? ' - ' + s.status : ''}`,
  }))
)

const openTuitionModal = () => {
  tuitionForm.reset()
  tuitionForm.clearErrors()
  tuitionForm.semester_id = props.semesters.find((s) => s.status === 'open' || s.status === 'active')?.id ?? props.semesters[0]?.id ?? ''
  showTuitionModal.value = true
}

const generateTuition = () => {
  tuitionForm.post('/invoices/generate-tuition', {
    preserveScroll: true,
    onSuccess: () => {
      showTuitionModal.value = false
    },
  })
}

const columns = [
  { key: 'number', label: 'Invoice' },
  { key: 'student', label: 'Student' },
  { key: 'due', label: 'Due' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'balance', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
]
// Soft search: the list follows the filters as they change — typed text after a
// short pause, picked options at once — so there is no search button.
const { applyNow, searching } = useLiveFilters(apply, { text: [search], choices: [status] })
</script>

<template>
  <Head title="Invoices - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Operations" title="Invoices" description="Charges and payment records per student. Payments are recorded by staff; there is no online payment.">
      <template #actions>
        <IconButton v-if="canManage" :icon="Calculator" size="md" label="Generate tuition invoices" @click="openTuitionModal" />
        <IconButton :icon="Download" :href="csvUrl" native size="md" label="Export CSV" />
        <IconButton v-if="canManage" :icon="Plus" href="/invoices/create" size="md" variant="primary" label="New invoice" />
      </template>
    </PageHeader>

    <form class="grid gap-3 sm:grid-cols-[2fr_1fr] sm:items-end" @submit.prevent="applyNow">
      <BaseInput v-model="search" :loading="searching" name="search" label="Search" placeholder="Invoice number, title, student…" />
      <BaseSelect v-model="status" :options="statusOptions" label="Status" />
    </form>

    <BaseTable :columns="columns" :rows="invoices.data" :row-href="(row) => `/invoices/${row.id}`" caption="Invoices" empty-title="No invoices" empty-description="Create an invoice to bill a student.">
      <template #cell-number="{ row }">
        <p class="font-mono font-medium">{{ row.invoice_number }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.title }}</p>
      </template>
      <template #cell-student="{ row }">
        <p>{{ row.student.full_name }}</p>
        <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
      </template>
      <template #cell-due="{ row }">{{ row.due_date }}</template>
      <template #cell-total="{ row }"><span class="tabular-nums">{{ money(row.total, row.currency) }}</span></template>
      <template #cell-balance="{ row }"><span class="font-semibold tabular-nums">{{ money(row.status === 'cancelled' ? 0 : row.balance, row.currency) }}</span></template>
      <template #cell-status="{ row }"><StatusBadge v-bind="invoiceBadge(row.status)" /></template>
    </BaseTable>

    <Pagination :links="invoices.meta?.links ?? []" />

    <BaseModal v-model="showTuitionModal" title="Generate tuition invoices">
      <form id="tuition-form" class="space-y-4" @submit.prevent="generateTuition">
        <p class="text-sm text-muted dark:text-dark-muted">
          Generate tuition invoices for all students with confirmed course enrollments in the selected semester.
          Each student is billed for their total enrolled credits. Students with existing non-cancelled tuition invoices for this semester are automatically skipped.
        </p>

        <BaseSelect
          v-model="tuitionForm.semester_id"
          name="semester_id"
          label="Semester"
          required
          :options="semesterOptions"
          :error="tuitionForm.errors.semester_id"
        />

        <div class="grid gap-4 sm:grid-cols-2">
          <BaseInput
            v-model="tuitionForm.due_date"
            name="due_date"
            label="Due date (optional)"
            type="date"
            placeholder="YYYY-MM-DD"
            :error="tuitionForm.errors.due_date"
          />
          <BaseInput
            v-model="tuitionForm.rate_per_credit"
            name="rate_per_credit"
            label="Rate per credit ($ optional)"
            type="number"
            step="0.01"
            min="0"
            placeholder="Default ($50 or program rate)"
            :error="tuitionForm.errors.rate_per_credit"
          />
        </div>

        <div class="flex items-center gap-2 pt-2">
          <input
            id="dry_run"
            v-model="tuitionForm.dry_run"
            type="checkbox"
            class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
          />
          <label for="dry_run" class="text-sm text-text dark:text-dark-text">
            Preview / Dry run only (simulate without creating actual invoices)
          </label>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showTuitionModal = false">Cancel</BaseButton>
          <BaseButton type="submit" form="tuition-form" variant="primary" :loading="tuitionForm.processing">
            {{ tuitionForm.dry_run ? 'Run preview' : 'Generate invoices' }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
