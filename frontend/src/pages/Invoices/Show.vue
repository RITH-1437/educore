<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'
import { categoryLabel, invoiceBadge, methodLabel, money } from '../../utils/finance'

const props = defineProps({
  invoice: { type: Object, required: true },
  methods: { type: Array, default: () => [] },
  today: { type: String, required: true },
})

const page = usePage()
const { confirm } = useConfirm()
// Students reach this page read-only; managers get the actions (server enforces).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))
const cancelled = computed(() => props.invoice.status === 'cancelled')
const editable = computed(() => canManage.value && !cancelled.value && props.invoice.payments.length === 0)
const fmt = (amount) => money(amount, props.invoice.currency)

const payment = useForm({ amount: props.invoice.balance, paid_on: props.today, method: 'cash', reference: '', notes: '' })
const methodOptions = computed(() => props.methods.map((m) => ({ value: m, label: methodLabel(m) })))
const pay = () => payment.post(`/invoices/${props.invoice.id}/payments`, { preserveScroll: true, onSuccess: () => payment.reset('reference', 'notes') })

const reversing = ref(null)
const reverseForm = useForm({ reason: '' })
const showReverse = computed({ get: () => reversing.value !== null, set: (open) => { if (!open) reversing.value = null } })
const openReverse = (row) => {
  reversing.value = row
  reverseForm.reset()
  reverseForm.clearErrors()
}
const reverse = () => reverseForm.post(`/payments/${reversing.value.id}/reverse`, { preserveScroll: true, onSuccess: () => (reversing.value = null) })

const cancelInvoice = async () => {
  if (await confirm({ title: `Cancel ${props.invoice.invoice_number}?`, message: 'The invoice stays on record as cancelled and can no longer receive payments.', confirmLabel: 'Cancel invoice', destructive: true })) {
    router.post(`/invoices/${props.invoice.id}/cancel`, {}, { preserveScroll: true })
  }
}
</script>

<template>
  <Head :title="`${invoice.invoice_number} - EduCore`" />
  <div class="mx-auto max-w-5xl space-y-6">
    <PageHeader eyebrow="Invoice" :title="invoice.invoice_number" :description="`${invoice.title} · ${invoice.student.full_name} (${invoice.student.student_number})`">
      <template #actions>
        <BaseButton :href="canManage ? '/invoices' : '/my-invoices'" variant="secondary">Back</BaseButton>
        <BaseButton v-if="editable" :href="`/invoices/${invoice.id}/edit`" variant="secondary">Edit</BaseButton>
        <BaseButton v-if="canManage && !cancelled && invoice.amount_paid === 0" variant="ghost" @click="cancelInvoice">Cancel invoice</BaseButton>
      </template>
    </PageHeader>

    <BaseCard padding="lg">
      <div class="grid gap-4 sm:grid-cols-4">
        <div><p class="text-caption text-muted dark:text-dark-muted">Status</p><StatusBadge class="mt-1" v-bind="invoiceBadge(invoice.status)" /></div>
        <div><p class="text-caption text-muted dark:text-dark-muted">Issued · due</p><p class="text-small text-ink dark:text-dark-ink">{{ invoice.issued_date }} · {{ invoice.due_date }}</p></div>
        <div><p class="text-caption text-muted dark:text-dark-muted">Total · paid</p><p class="text-small tabular-nums text-ink dark:text-dark-ink">{{ fmt(invoice.total) }} · {{ fmt(invoice.amount_paid) }}</p></div>
        <div><p class="text-caption text-muted dark:text-dark-muted">Balance</p><p class="text-h4 font-semibold tabular-nums" :class="invoice.status === 'overdue' ? 'text-error' : 'text-ink dark:text-dark-ink'">{{ fmt(cancelled ? 0 : invoice.balance) }}</p></div>
      </div>
      <p v-if="invoice.description" class="mt-4 text-small text-muted dark:text-dark-muted">{{ invoice.description }}</p>
    </BaseCard>

    <BaseCard title="Items" padding="lg">
      <div class="-mx-2 overflow-x-auto">
        <table class="min-w-full">
          <caption class="sr-only">Invoice items</caption>
          <thead>
            <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
              <th scope="col" class="px-2 py-2">Description</th>
              <th scope="col" class="px-2 py-2">Category</th>
              <th scope="col" class="px-2 py-2 text-right">Qty</th>
              <th scope="col" class="px-2 py-2 text-right">Unit price</th>
              <th scope="col" class="px-2 py-2 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-default text-small dark:divide-dark-border">
            <tr v-for="item in invoice.items" :key="item.id">
              <td class="px-2 py-2 text-ink dark:text-dark-ink">{{ item.description }}</td>
              <td class="px-2 py-2 text-muted dark:text-dark-muted">{{ categoryLabel(item.fee_category) }}</td>
              <td class="px-2 py-2 text-right tabular-nums">{{ item.quantity }}</td>
              <td class="px-2 py-2 text-right tabular-nums">{{ fmt(item.unit_price) }}</td>
              <td class="px-2 py-2 text-right tabular-nums">{{ fmt(item.amount) }}</td>
            </tr>
          </tbody>
          <tfoot class="text-small">
            <tr><td colspan="4" class="px-2 pt-3 text-right text-muted dark:text-dark-muted">Subtotal</td><td class="px-2 pt-3 text-right tabular-nums">{{ fmt(invoice.subtotal) }}</td></tr>
            <tr v-if="invoice.discount > 0"><td colspan="4" class="px-2 text-right text-muted dark:text-dark-muted">Discount</td><td class="px-2 text-right tabular-nums">−{{ fmt(invoice.discount) }}</td></tr>
            <tr><td colspan="4" class="px-2 text-right font-semibold text-ink dark:text-dark-ink">Total</td><td class="px-2 text-right font-semibold tabular-nums text-ink dark:text-dark-ink">{{ fmt(invoice.total) }}</td></tr>
          </tfoot>
        </table>
      </div>
    </BaseCard>

    <div class="grid gap-6" :class="canManage && !cancelled && invoice.balance > 0 ? 'lg:grid-cols-[2fr_1fr]' : ''">
      <BaseCard title="Payments" padding="lg">
        <EmptyState v-if="!invoice.payments.length" title="No payments yet" description="Recorded payments and reversals appear here." />
        <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
          <li v-for="row in invoice.payments" :key="row.id" class="flex flex-wrap items-start justify-between gap-3 py-3">
            <div class="min-w-0">
              <p class="text-small font-medium" :class="row.is_reversal || row.reversed ? 'text-muted dark:text-dark-muted' : 'text-ink dark:text-dark-ink'">
                <span :class="row.reversed ? 'line-through' : ''">{{ row.is_reversal ? '−' : '' }}{{ fmt(row.amount) }}</span>
                · {{ row.is_reversal ? 'Reversal' : methodLabel(row.method) }} · {{ row.paid_on }}
              </p>
              <p class="text-caption text-muted dark:text-dark-muted">
                <span v-if="row.reference">Ref {{ row.reference }}</span><span v-if="row.notes"> · {{ row.notes }}</span><span v-if="row.received_by"> · by {{ row.received_by }}</span>
              </p>
            </div>
            <BaseButton v-if="canManage && !row.is_reversal && !row.reversed" size="sm" variant="ghost" @click="openReverse(row)">Reverse</BaseButton>
          </li>
        </ul>
      </BaseCard>

      <BaseCard v-if="canManage && !cancelled && invoice.balance > 0" title="Record payment" padding="lg">
        <form class="space-y-4" @submit.prevent="pay">
          <BaseInput v-model="payment.amount" name="amount" label="Amount" type="number" required :error="payment.errors.amount" />
          <BaseInput v-model="payment.paid_on" name="paid_on" label="Received on" type="date" required :error="payment.errors.paid_on" />
          <BaseSelect v-model="payment.method" :options="methodOptions" label="Method" :error="payment.errors.method" />
          <BaseInput v-model="payment.reference" name="reference" label="Reference (optional)" :error="payment.errors.reference" />
          <BaseButton type="submit" class="w-full" :loading="payment.processing">Record payment</BaseButton>
        </form>
      </BaseCard>
    </div>

    <p v-if="invoice.notes" class="whitespace-pre-line text-caption text-muted dark:text-dark-muted">{{ invoice.notes }}</p>

    <BaseModal v-model="showReverse" title="Reverse payment">
      <form id="reverse-form" @submit.prevent="reverse">
        <p class="mb-4 text-small text-muted dark:text-dark-muted">A reversal record is added; the original payment stays in the history.</p>
        <BaseTextarea v-model="reverseForm.reason" name="reason" label="Reason" required :rows="3" :error="reverseForm.errors.reason" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showReverse = false">Cancel</BaseButton>
          <BaseButton type="submit" form="reverse-form" variant="danger" :loading="reverseForm.processing">Reverse</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
