<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import PageHeader from '../../components/PageHeader.vue'
import { actionLabel, show, when } from '../../utils/audit'

const props = defineProps({
  log: { type: Object, required: true },
})

// One row per attribute present before or after; changed rows are marked.
const rows = computed(() => {
  const before = props.log.before ?? {}
  const after = props.log.after ?? {}
  return [...new Set([...Object.keys(before), ...Object.keys(after)])].map((key) => ({
    key,
    before: show(before[key]),
    after: show(after[key]),
    changed: JSON.stringify(before[key]) !== JSON.stringify(after[key]),
  }))
})
</script>

<template>
  <Head :title="`Audit #${log.id} - EduCore`" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="Audit log" :title="actionLabel(log.action)" :description="when(log.created_at)">
      <template #actions><BaseButton href="/audit-logs" variant="secondary">Back</BaseButton></template>
    </PageHeader>

    <BaseCard padding="lg">
      <dl class="grid gap-4 text-small sm:grid-cols-2">
        <div><dt class="text-caption text-muted dark:text-dark-muted">Who</dt><dd class="text-ink dark:text-dark-ink">{{ log.actor ? `${log.actor.name} (${log.actor.email})` : 'System / removed user' }}</dd></div>
        <div><dt class="text-caption text-muted dark:text-dark-muted">Record</dt><dd class="text-ink dark:text-dark-ink">{{ log.target ? `${log.target.type} #${log.target.id}` : '—' }}</dd></div>
        <div><dt class="text-caption text-muted dark:text-dark-muted">Action code</dt><dd class="font-mono text-ink dark:text-dark-ink">{{ log.action }}</dd></div>
        <div><dt class="text-caption text-muted dark:text-dark-muted">From</dt><dd class="font-mono text-ink dark:text-dark-ink">{{ log.ip_address ?? '—' }}</dd></div>
        <div v-if="log.description" class="sm:col-span-2"><dt class="text-caption text-muted dark:text-dark-muted">Description</dt><dd class="whitespace-pre-line text-ink dark:text-dark-ink">{{ log.description }}</dd></div>
        <div v-if="log.user_agent" class="sm:col-span-2"><dt class="text-caption text-muted dark:text-dark-muted">Browser</dt><dd class="break-all text-caption text-muted dark:text-dark-muted">{{ log.user_agent }}</dd></div>
      </dl>
    </BaseCard>

    <BaseCard v-if="rows.length" title="Values" padding="lg">
      <template #description>Only the attributes involved are kept; secrets such as passwords are never recorded.</template>
      <div class="-mx-2 overflow-x-auto">
        <table class="min-w-full text-small">
          <caption class="sr-only">Before and after values</caption>
          <thead>
            <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
              <th scope="col" class="px-2 py-2">Field</th>
              <th scope="col" class="px-2 py-2">Before</th>
              <th scope="col" class="px-2 py-2">After</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-default dark:divide-dark-border">
            <tr v-for="row in rows" :key="row.key" class="align-top">
              <th scope="row" class="px-2 py-2 text-left font-mono font-normal text-ink dark:text-dark-ink">
                {{ row.key }}
                <span v-if="row.changed" class="sr-only">(changed)</span>
              </th>
              <td class="px-2 py-2"><pre class="whitespace-pre-wrap break-all font-mono text-caption text-muted dark:text-dark-muted">{{ row.before }}</pre></td>
              <td class="px-2 py-2"><pre class="whitespace-pre-wrap break-all font-mono text-caption" :class="row.changed ? 'font-semibold text-ink dark:text-dark-ink' : 'text-muted dark:text-dark-muted'">{{ row.after }}</pre></td>
            </tr>
          </tbody>
        </table>
      </div>
    </BaseCard>
  </div>
</template>
