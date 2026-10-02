<script setup>
import { Trash2 } from '@lucide/vue'
import IconButton from '../../components/IconButton.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import { categoryLabel, money } from '../../utils/finance'

const props = defineProps({
  invoice: { type: Object, default: null },
  students: { type: Array, default: () => [] },
  currencies: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  today: { type: String, required: true },
})

const editing = computed(() => props.invoice !== null)
const blankItem = () => ({ description: '', quantity: 1, unit_price: '', fee_category: 'tuition' })

const form = useForm({
  student_id: props.invoice?.student?.id ?? '',
  title: props.invoice?.title ?? '',
  description: props.invoice?.description ?? '',
  currency: props.invoice?.currency ?? 'USD',
  issued_date: props.invoice?.issued_date ?? props.today,
  due_date: props.invoice?.due_date ?? '',
  discount: props.invoice?.discount ?? 0,
  notes: props.invoice?.notes ?? '',
  items: props.invoice?.items?.map(({ description, quantity, unit_price, fee_category }) => ({ description, quantity, unit_price, fee_category: fee_category ?? '' })) ?? [blankItem()],
})

const studentOptions = computed(() => props.students.map((s) => ({ value: s.id, label: s.label })))
const currencyOptions = computed(() => props.currencies.map((c) => ({ value: c, label: c })))
const categoryOptions = computed(() => props.categories.map((c) => ({ value: c, label: categoryLabel(c) })))

// Preview only; the server recomputes every total.
const preview = computed(() => {
  const subtotal = form.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0)
  return { subtotal, total: Math.max(0, subtotal - Number(form.discount || 0)) }
})

const submit = () => {
  const request = form.transform((data) => {
    const payload = { ...data, items: data.items.map((item) => ({ ...item, fee_category: item.fee_category || null })) }
    if (editing.value) delete payload.student_id
    return payload
  })
  editing.value ? request.put(`/invoices/${props.invoice.id}`) : request.post('/invoices')
}
const itemError = (i, field) => form.errors[`items.${i}.${field}`]
</script>

<template>
  <Head :title="`${editing ? 'Edit' : 'New'} invoice - EduCore`" />
  <div class="mx-auto max-w-5xl space-y-6">
    <PageHeader eyebrow="Operations" :title="editing ? `Edit ${invoice.invoice_number}` : 'New invoice'" description="Totals are computed from the items. Invoices can be edited until a payment is recorded.">
      <template #actions>
        <BaseButton :href="editing ? `/invoices/${invoice.id}` : '/invoices'" variant="secondary">Cancel</BaseButton>
      </template>
    </PageHeader>

    <form class="space-y-6" @submit.prevent="submit">
      <BaseCard title="Details" padding="lg">
        <div class="grid gap-4 sm:grid-cols-2">
          <BaseSelect v-if="!editing" v-model="form.student_id" :options="studentOptions" label="Student" placeholder="Choose a student" :error="form.errors.student_id" />
          <p v-else class="text-small text-ink dark:text-dark-ink">Student: <span class="font-medium">{{ invoice.student.full_name }}</span> ({{ invoice.student.student_number }})</p>
          <BaseInput v-model="form.title" name="title" label="Title" required :error="form.errors.title" />
          <BaseInput v-model="form.issued_date" name="issued_date" label="Issue date" type="date" :error="form.errors.issued_date" />
          <BaseInput v-model="form.due_date" name="due_date" label="Due date" type="date" required :error="form.errors.due_date" />
          <BaseSelect v-model="form.currency" :options="currencyOptions" label="Currency" :error="form.errors.currency" />
          <BaseInput v-model="form.discount" name="discount" label="Discount" type="number" :error="form.errors.discount" />
        </div>
        <BaseTextarea v-model="form.description" class="mt-4" name="description" label="Description (optional)" :rows="2" :error="form.errors.description" />
      </BaseCard>

      <BaseCard title="Items" padding="lg">
        <div class="space-y-3">
          <div v-for="(item, i) in form.items" :key="i" class="grid items-start gap-3 sm:grid-cols-[3fr_1fr_1fr_1.5fr_auto]">
            <BaseInput v-model="item.description" :name="`item-${i}-description`" label="Description" required :error="itemError(i, 'description')" />
            <BaseInput v-model="item.quantity" :name="`item-${i}-quantity`" label="Qty" type="number" required :error="itemError(i, 'quantity')" />
            <BaseInput v-model="item.unit_price" :name="`item-${i}-price`" label="Unit price" type="number" required :error="itemError(i, 'unit_price')" />
            <BaseSelect v-model="item.fee_category" :options="categoryOptions" label="Category" placeholder="None" :error="itemError(i, 'fee_category')" />
            <IconButton class="sm:mt-7" :icon="Trash2" variant="danger" :disabled="form.items.length <= 1" :label="`Remove item ${i + 1}`" @click="form.items.splice(i, 1)" />
          </div>
        </div>
        <ErrorAlert v-if="form.errors.items" class="mt-4" title="Items" :message="form.errors.items" />
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border-default pt-4 dark:border-dark-border">
          <BaseButton variant="secondary" :disabled="form.items.length >= 50" @click="form.items.push(blankItem())">Add item</BaseButton>
          <p class="text-small text-muted dark:text-dark-muted">
            Subtotal <span class="tabular-nums text-ink dark:text-dark-ink">{{ money(preview.subtotal, form.currency) }}</span> ·
            Total <span class="font-semibold tabular-nums text-ink dark:text-dark-ink">{{ money(preview.total, form.currency) }}</span>
          </p>
        </div>
      </BaseCard>

      <BaseCard title="Notes" padding="lg">
        <BaseTextarea v-model="form.notes" name="notes" label="Internal notes (optional)" :rows="2" :error="form.errors.notes" />
      </BaseCard>

      <div class="flex justify-end">
        <BaseButton type="submit" :loading="form.processing">{{ editing ? 'Save changes' : 'Create invoice' }}</BaseButton>
      </div>
    </form>
  </div>
</template>
