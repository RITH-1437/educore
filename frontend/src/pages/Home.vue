<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ArrowRight, LayoutDashboard } from '@lucide/vue'
import BaseBadge from '../components/BaseBadge.vue'
import BaseButton from '../components/BaseButton.vue'
import BaseCard from '../components/BaseCard.vue'

const user = computed(() => usePage().props.auth?.user ?? null)
const dashboardUrl = computed(() => user.value?.role?.slug === 'super-admin' ? '/admin/dashboard' : '/dashboard')
</script>

<template>
  <Head title="Home - EduCore" />
  <div class="mx-auto max-w-3xl space-y-6">
    <section>
      <p class="text-caption font-semibold uppercase tracking-widest text-primary">One Platform. Smarter Education.</p>
      <h2 class="mt-2 text-h1 font-display font-semibold text-ink dark:text-dark-ink">Welcome, {{ user?.name }}</h2>
      <p class="mt-2 text-body text-muted dark:text-dark-muted">Your EduCore account is ready. Open your role workspace to see the tools prepared for you.</p>
    </section>
    <BaseCard padding="lg">
      <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary"><LayoutDashboard class="h-6 w-6" aria-hidden="true" /></span>
        <div class="min-w-0 flex-1">
          <h3 class="text-h3 font-semibold text-ink dark:text-dark-ink">{{ user?.role?.name ?? 'Account' }} workspace</h3>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">Continue to your role-specific dashboard preview.</p>
          <BaseBadge class="mt-3" variant="success" dot>{{ user?.is_active ? 'Active account' : 'Inactive account' }}</BaseBadge>
        </div>
        <Link :href="dashboardUrl"><BaseButton>Open dashboard <ArrowRight class="h-4 w-4" aria-hidden="true" /></BaseButton></Link>
      </div>
    </BaseCard>
  </div>
</template>
