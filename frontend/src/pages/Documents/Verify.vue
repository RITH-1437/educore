<script>
import GuestLayout from '../../layouts/GuestLayout.vue'

export default { layout: GuestLayout }
</script>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ShieldAlert, ShieldCheck, ShieldX } from '@lucide/vue'
import BaseCard from '../../components/BaseCard.vue'

const props = defineProps({
  token: { type: String, required: true },
  result: { type: Object, default: null },
})

const state = computed(() => {
  if (!props.result) return { icon: ShieldX, tone: 'text-error', title: 'Not found', text: 'No document issued by this university matches this verification code.' }
  if (props.result.status === 'valid') return { icon: ShieldCheck, tone: 'text-success', title: 'Valid document', text: 'This document was issued by the university and has not been revoked.' }
  return { icon: ShieldAlert, tone: 'text-warning', title: 'Revoked document', text: 'This document was issued but has since been revoked; it is no longer valid.' }
})
</script>

<template>
  <Head title="Verify a document - EduCore" />
  <div class="mx-auto flex min-h-screen max-w-xl flex-col justify-center px-4 py-12">
    <p class="mb-4 text-center text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">EduCore document verification</p>
    <BaseCard padding="lg">
      <div class="flex items-start gap-4">
        <component :is="state.icon" class="size-10 shrink-0" :class="state.tone" aria-hidden="true" />
        <div>
          <h1 class="text-h3 font-semibold text-ink dark:text-dark-ink">{{ state.title }}</h1>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ state.text }}</p>
        </div>
      </div>

      <dl v-if="result" class="mt-6 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-small">
        <dt class="text-muted dark:text-dark-muted">Document</dt>
        <dd class="text-ink dark:text-dark-ink">{{ result.document_type }}<span v-if="result.semester"> · {{ result.semester }}</span></dd>
        <dt class="text-muted dark:text-dark-muted">Issued to</dt>
        <dd class="text-ink dark:text-dark-ink">{{ result.issued_to }} ({{ result.student_number }})</dd>
        <dt class="text-muted dark:text-dark-muted">Issued on</dt>
        <dd class="text-ink dark:text-dark-ink">{{ result.issued_on }}</dd>
        <dt v-if="result.issuer" class="text-muted dark:text-dark-muted">Issuer</dt>
        <dd v-if="result.issuer" class="text-ink dark:text-dark-ink">{{ result.issuer }}</dd>
        <dt class="text-muted dark:text-dark-muted">SHA-256</dt>
        <dd class="break-all font-mono text-caption text-ink dark:text-dark-ink">{{ result.checksum }}</dd>
      </dl>

      <p class="mt-6 break-all border-t border-border-default pt-4 font-mono text-caption text-muted dark:border-dark-border dark:text-dark-muted">Code {{ token }}</p>
    </BaseCard>
    <p class="mt-4 text-center text-caption text-muted dark:text-dark-muted">The checksum lets you confirm the PDF you hold is the unaltered original.</p>
  </div>
</template>
