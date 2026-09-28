<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowDownRight,
  ArrowRight,
  ArrowUpRight,
  BookOpen,
  CalendarDays,
  GraduationCap,
  Landmark,
  ShieldCheck,
  Users,
} from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  roleCounts: { type: Array, default: () => [] },
  currentAcademicYear: { type: Object, default: null },
  recentUsers: { type: Array, default: () => [] },
})

const metrics = computed(() => [
  { label: 'Total accounts', value: props.stats.total_users, detail: `${props.stats.active_users} active`, icon: Users, href: '/users', tone: 'primary' },
  { label: 'Academic years', value: props.stats.total_academic_years, detail: `${props.stats.active_academic_years} active`, icon: CalendarDays, href: '/academic-years', tone: 'secondary' },
  { label: 'Faculties', value: props.stats.total_faculties, detail: 'Institution structure', icon: Landmark, href: '', tone: 'success' },
  { label: 'Programs', value: props.stats.total_programs, detail: `${props.stats.total_semesters} semesters`, icon: BookOpen, href: '', tone: 'warning' },
])

const toneClasses = {
  primary: 'bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary',
  secondary: 'bg-secondary/15 text-primary-dark dark:bg-secondary/15 dark:text-dark-ink',
  success: 'bg-success/10 text-success dark:bg-success/15 dark:text-green-300',
  warning: 'bg-warning/15 text-primary-dark dark:bg-warning/15 dark:text-amber-200',
}

const managementAreas = [
  { title: 'User & role management', detail: 'Create accounts, assign fixed platform roles, and manage access.', href: '/users', label: 'Manage users', icon: Users, ready: true },
  { title: 'Academic calendar', detail: 'Manage academic years, semester dates, and status transitions.', href: '/academic-years', label: 'Open calendar', icon: CalendarDays, ready: true },
  { title: 'Faculties & departments', detail: 'Organize the university structure and responsible administrators.', href: '', label: 'Planned', icon: Landmark, ready: false },
  { title: 'Programs & courses', detail: 'Review and manage academic offerings across the institution.', href: '', label: 'Planned', icon: BookOpen, ready: false },
  { title: 'Students & lecturers', detail: 'Manage academic profiles and institutional memberships.', href: '', label: 'Planned', icon: GraduationCap, ready: false },
  { title: 'Platform security', detail: 'A future home for audit, system configuration, and security controls.', href: '', label: 'Planned', icon: ShieldCheck, ready: false },
]
</script>

<template>
  <Head title="Admin dashboard - EduCore" />

  <div class="space-y-8">
    <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
      <div>
        <p class="mb-2 text-caption font-semibold uppercase tracking-widest text-primary">Platform overview</p>
        <h2 class="text-h1 font-display font-semibold tracking-tight text-ink dark:text-dark-ink">Good day, administrator</h2>
        <p class="mt-2 max-w-2xl text-body text-muted dark:text-dark-muted">Manage the university platform, review academic operations, and find the tools you need from one place.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <Link href="/academic-years/create"><BaseButton variant="secondary"><CalendarDays class="h-4 w-4" aria-hidden="true" /> New academic year</BaseButton></Link>
        <Link href="/users/create"><BaseButton><Users class="h-4 w-4" aria-hidden="true" /> Create account</BaseButton></Link>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Platform metrics">
      <BaseCard v-for="(metric, index) in metrics" :key="metric.label" :href="metric.href || undefined" :hoverable="Boolean(metric.href)" class="motion-safe:animate-slide-up" :style="{ animationDelay: `${index * 50}ms` }">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-small font-medium text-muted dark:text-dark-muted">{{ metric.label }}</p>
            <p class="mt-3 text-h2 font-semibold tabular-nums text-ink dark:text-dark-ink">{{ metric.value }}</p>
            <p class="mt-1 text-caption text-muted dark:text-dark-muted">{{ metric.detail }}</p>
          </div>
          <span :class="['flex h-11 w-11 items-center justify-center rounded-xl', toneClasses[metric.tone]]">
            <component :is="metric.icon" class="h-5 w-5" aria-hidden="true" />
          </span>
        </div>
        <span v-if="metric.href" class="mt-4 inline-flex items-center gap-1 text-caption font-semibold text-primary dark:text-dark-primary">Open section <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" /></span>
        <span v-else class="mt-4 inline-flex"><BaseBadge variant="muted" size="sm">Planned module</BaseBadge></span>
      </BaseCard>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <BaseCard title="Management directory" padding="lg">
        <template #description>Current tools and the management areas planned for this super-admin workspace.</template>
        <div class="grid gap-3 md:grid-cols-2">
          <article v-for="area in managementAreas" :key="area.title" class="group flex min-h-36 flex-col rounded-lg border border-border-default p-4 transition duration-150 ease-out hover:border-border-muted hover:shadow-sm dark:border-dark-border dark:hover:border-dark-muted motion-reduce:transition-none">
            <div class="flex items-start gap-3">
              <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-lg', area.ready ? 'bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary' : 'bg-background text-muted dark:bg-dark-surface-2 dark:text-dark-muted']">
                <component :is="area.icon" class="h-5 w-5" aria-hidden="true" />
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ area.title }}</h3>
                  <BaseBadge v-if="!area.ready" variant="muted" size="sm">Planned</BaseBadge>
                </div>
                <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ area.detail }}</p>
              </div>
            </div>
            <Link v-if="area.ready" :href="area.href" class="mt-auto inline-flex items-center gap-1 pt-4 text-small font-semibold text-primary hover:underline dark:text-dark-primary">
              {{ area.label }} <ArrowRight class="h-4 w-4" aria-hidden="true" />
            </Link>
            <span v-else class="mt-auto pt-4 text-caption text-muted dark:text-dark-muted">This module will be added in a future phase.</span>
          </article>
        </div>
      </BaseCard>

      <div class="space-y-6">
        <BaseCard title="Current academic year">
          <template #description>Institution-wide calendar context.</template>
          <div v-if="currentAcademicYear" class="mt-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="text-h3 font-semibold text-ink dark:text-dark-ink">{{ currentAcademicYear.name }}</p>
                <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ currentAcademicYear.code }} · {{ currentAcademicYear.start_date }} – {{ currentAcademicYear.end_date }}</p>
              </div>
              <StatusBadge :status="currentAcademicYear.status" />
            </div>
            <div class="mt-5 space-y-3">
              <div v-for="semester in currentAcademicYear.semesters" :key="semester.id" class="flex items-center justify-between gap-3 border-t border-border-default pt-3 dark:border-dark-border">
                <div>
                  <p class="text-small font-medium text-ink dark:text-dark-ink">{{ semester.sequence }}. {{ semester.name }}</p>
                  <p class="text-caption text-muted dark:text-dark-muted">{{ semester.start_date || 'Dates not set' }}<template v-if="semester.end_date"> – {{ semester.end_date }}</template></p>
                </div>
                <StatusBadge :status="semester.status" size="sm" />
              </div>
              <p v-if="currentAcademicYear.semesters.length === 0" class="text-small text-muted dark:text-dark-muted">No semesters are configured yet.</p>
            </div>
            <Link href="/academic-years" class="mt-5 inline-flex items-center gap-1 text-small font-semibold text-primary hover:underline dark:text-dark-primary">Manage academic calendar <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link>
          </div>
          <EmptyState v-else title="No current academic year" description="Create and activate an academic year to set the current calendar." action-label="Create academic year" action-href="/academic-years/create" />
        </BaseCard>

        <BaseCard title="Accounts by role">
          <template #description>Active and inactive accounts included.</template>
          <ul v-if="roleCounts.length" class="mt-4 divide-y divide-border-default dark:divide-dark-border">
            <li v-for="roleItem in roleCounts" :key="roleItem.slug" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
              <span class="text-small text-ink dark:text-dark-ink">{{ roleItem.name }}</span>
              <span class="rounded-pill bg-background px-3 py-1 text-small font-semibold tabular-nums text-ink dark:bg-dark-surface-2 dark:text-dark-ink">{{ roleItem.total }}</span>
            </li>
          </ul>
          <EmptyState v-else title="No roles assigned" description="Accounts will appear here as roles are assigned." />
        </BaseCard>
      </div>
    </section>

    <BaseCard title="Recently created accounts" padding="lg">
      <template #description>Latest platform accounts, with access role and account status.</template>
      <template #actions><Link href="/users" class="inline-flex items-center gap-1 text-small font-semibold text-primary hover:underline dark:text-dark-primary">View all <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link></template>
      <div v-if="recentUsers.length" class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-border-default dark:divide-dark-border">
          <thead><tr><th class="px-3 py-3 text-left text-small font-semibold text-muted dark:text-dark-muted">Name</th><th class="px-3 py-3 text-left text-small font-semibold text-muted dark:text-dark-muted">Email</th><th class="px-3 py-3 text-left text-small font-semibold text-muted dark:text-dark-muted">Role</th><th class="px-3 py-3 text-left text-small font-semibold text-muted dark:text-dark-muted">Status</th><th class="px-3 py-3 text-right text-small font-semibold text-muted dark:text-dark-muted">Created</th></tr></thead>
          <tbody class="divide-y divide-border-default dark:divide-dark-border">
            <tr v-for="(account, index) in recentUsers" :key="account.id" class="motion-safe:animate-fade-in">
              <td class="px-3 py-3 text-small font-medium text-ink dark:text-dark-ink">{{ account.name }}</td>
              <td class="px-3 py-3 text-small text-muted dark:text-dark-muted">{{ account.email }}</td>
              <td class="px-3 py-3 text-small text-ink dark:text-dark-ink">{{ account.role || '—' }}</td>
              <td class="px-3 py-3"><StatusBadge :status="account.is_active ? 'active' : 'inactive'" size="sm" /></td>
              <td class="px-3 py-3 text-right text-small text-muted dark:text-dark-muted">{{ account.created_at ? new Date(account.created_at).toLocaleDateString() : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState v-else title="No user accounts yet" description="Create the first account to begin setting up EduCore." action-label="Create account" action-href="/users/create" />
    </BaseCard>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border-default bg-surface px-5 py-4 text-small text-muted dark:border-dark-border dark:bg-dark-surface dark:text-dark-muted">
      <span>Administrative actions are protected by server-side role and policy checks.</span>
      <span class="inline-flex items-center gap-2"><ArrowUpRight class="h-4 w-4 text-success" aria-hidden="true" /><ArrowDownRight class="h-4 w-4 text-error" aria-hidden="true" /> Metrics are current totals; trends will be introduced with analytics.</span>
    </div>
  </div>
</template>
