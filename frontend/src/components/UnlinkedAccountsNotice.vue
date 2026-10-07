<script setup>
import { computed } from 'vue'
import { TriangleAlert, UserPlus } from '@lucide/vue'
import IconButton from './IconButton.vue'

// Students and Lecturers list profiles, not accounts: an account given the
// Student or Lecturer role in Users management has no profile until one is
// created, so it is missing from the list. This notice names those accounts
// and hands each one to the page's "New …" form, already linked.
const props = defineProps({
  accounts: { type: Array, default: () => [] },
  /** `student` or `lecturer` — the profile the accounts are missing. */
  role: { type: String, required: true },
})

defineEmits(['link'])

const heading = computed(() => `${props.accounts.length} ${props.role} ${props.accounts.length === 1 ? 'account has' : 'accounts have'} no profile`)
const roleName = computed(() => props.role.charAt(0).toUpperCase() + props.role.slice(1))
</script>

<template>
  <section v-if="accounts.length" class="rounded-xl border border-warning/30 bg-warning/5 p-4 sm:p-5" :aria-label="`${roleName} accounts without a profile`">
    <h2 class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink"><TriangleAlert class="h-4 w-4 shrink-0 text-warning" aria-hidden="true" /> {{ heading }}</h2>
    <p class="mt-1 text-small text-muted dark:text-dark-muted">
      {{ accounts.length === 1 ? 'It has' : 'They have' }} the {{ roleName }} role in Users but no {{ role }} profile yet, so {{ accounts.length === 1 ? 'it is' : 'they are' }} not in the list below. Create the profile to add {{ accounts.length === 1 ? 'it' : 'them' }}.
    </p>
    <ul class="mt-3 divide-y divide-warning/20">
      <li v-for="account in accounts" :key="account.id" class="flex items-center justify-between gap-3 py-2 last:pb-0">
        <div class="min-w-0">
          <p class="truncate text-small font-medium text-ink dark:text-dark-ink">{{ account.name }}</p>
          <p class="truncate text-caption text-muted dark:text-dark-muted">{{ account.email }}</p>
        </div>
        <IconButton :icon="UserPlus" :label="`Create ${role} profile for ${account.name}`" @click="$emit('link', account)" />
      </li>
    </ul>
  </section>
</template>
