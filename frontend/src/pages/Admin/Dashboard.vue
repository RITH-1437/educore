<script setup>
import IconButton from '../../components/IconButton.vue'
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, BookOpen, CalendarDays, CalendarPlus, GraduationCap, Landmark, Presentation, TriangleAlert, UserPlus, Users } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import PieChart from '../../components/charts/PieChart.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  roleCounts: { type: Array, default: () => [] },
  currentAcademicYear: { type: Object, default: null },
  recentUsers: { type: Array, default: () => [] },
})

const metrics = computed(() => [
  { label: 'Total accounts', value: props.stats.total_users, detail: `${props.stats.active_users} active`, href: '/users', tone: 'primary' },
  { label: 'Academic years', value: props.stats.total_academic_years, detail: `${props.stats.active_academic_years} active`, href: '/academic-years', tone: 'secondary' },
  { label: 'Departments', value: props.stats.total_departments, detail: 'Institution structure', href: '/departments', tone: 'success' },
  { label: 'Programs', value: props.stats.total_programs, detail: `${props.stats.total_semesters} semesters`, href: '/programs', tone: 'warning' },
])

// Only real, derivable signals — nothing is shown when everything is in order.
const attention = computed(() => {
  const items = []
  const inactive = props.stats.total_users - props.stats.active_users
  if (!props.currentAcademicYear) items.push({ text: 'No current academic year is set.', href: '/academic-years', action: 'Set the current academic year' })
  if (props.currentAcademicYear && props.stats.total_semesters === 0) items.push({ text: 'No semesters exist yet.', href: `/academic-years/${props.currentAcademicYear.id}/edit`, action: 'Add a semester' })
  if (inactive > 0) items.push({ text: `${inactive} ${inactive === 1 ? 'account is' : 'accounts are'} inactive.`, href: '/users', action: 'Review inactive accounts' })
  return items
})

const areas = [
  { title: 'Users & roles', detail: 'Create accounts and assign platform roles.', href: '/users', icon: Users },
  { title: 'Academic calendar', detail: 'Years, semesters, and status transitions.', href: '/academic-years', icon: CalendarDays },
  { title: 'Departments', detail: 'Organize the university structure.', href: '/departments', icon: Landmark },
  { title: 'Programs', detail: 'Degree tracks offered by each department.', href: '/programs', icon: BookOpen },
  { title: 'Courses', detail: 'Catalog, prerequisites and program curricula.', href: '/courses', icon: GraduationCap },
  { title: 'Offerings & sections', detail: 'Classes per semester and their lecturers.', href: '/offerings', icon: CalendarDays },
  { title: 'Lecturers', detail: 'Teaching staff profiles and accounts.', href: '/lecturers', icon: Presentation },
  { title: 'Students', detail: 'Profiles, status and program history.', href: '/students', icon: Users },
]

const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '—')
const delay = (step) => ({ animationDelay: `${step * 60}ms` })

// Accounts by role: brand palette tokens (primary, secondary, accent, muted, dark-muted), one per role.
const roleColors = ['#2563EB', '#38BDF8', '#14B8A6', '#64748B', '#94A3B8']
const roleChart = computed(() => ({
  labels: props.roleCounts.map((role) => role.name),
  values: props.roleCounts.map((role) => role.total),
}))
</script>

<template>
  <Head title="Admin dashboard - EduCore" />

  <div class="space-y-8">
    <PageHeader title="Platform overview" description="A snapshot of accounts, the academic calendar, and what needs your attention.">
      <template #actions>
        <IconButton :icon="CalendarPlus" href="/academic-years/create" size="md" label="New academic year" />
        <IconButton :icon="UserPlus" href="/users/create" size="md" variant="primary" label="Create account" />
      </template>
    </PageHeader>

    <section v-if="attention.length" class="rounded-xl border border-warning/30 bg-warning/5 p-4 motion-safe:animate-section-in sm:p-5" aria-label="Needs attention">
      <h2 class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink"><TriangleAlert class="h-4 w-4 text-warning" aria-hidden="true" /> Needs attention</h2>
      <ul class="mt-3 divide-y divide-warning/20">
        <li v-for="item in attention" :key="item.text" class="flex flex-wrap items-center justify-between gap-2 py-2 first:pt-0 last:pb-0">
          <span class="text-small text-ink dark:text-dark-ink">{{ item.text }}</span>
          <IconButton :icon="ArrowRight" :href="item.href" :label="item.action" />
        </li>
      </ul>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Platform metrics">
      <StatCard v-for="(metric, index) in metrics" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index)" />
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
      <BaseCard title="Recently created accounts" padding="lg" class="min-w-0 motion-safe:animate-section-in" :style="delay(4)">
        <template #description>Latest platform accounts with their role and status.</template>
        <template #actions><IconButton :icon="ArrowRight" href="/users" label="View all accounts" /></template>
        <div v-if="recentUsers.length" class="-mx-2 overflow-x-auto">
          <table class="min-w-full">
            <caption class="sr-only">Recently created accounts</caption>
            <thead>
              <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
                <th scope="col" class="px-2 py-2">Account</th>
                <th scope="col" class="hidden px-2 py-2 sm:table-cell">Role</th>
                <th scope="col" class="px-2 py-2">Status</th>
                <th scope="col" class="hidden px-2 py-2 text-right md:table-cell">Created</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-default dark:divide-dark-border">
              <tr v-for="account in recentUsers" :key="account.id" class="transition-colors duration-150 hover:bg-background dark:hover:bg-dark-surface-2/50">
                <td class="px-2 py-3">
                  <p class="text-small font-medium text-ink dark:text-dark-ink">{{ account.name }}</p>
                  <p class="max-w-56 truncate text-caption text-muted dark:text-dark-muted">{{ account.email }}</p>
                </td>
                <td class="hidden px-2 py-3 text-small text-ink dark:text-dark-ink sm:table-cell">{{ account.role || '—' }}</td>
                <td class="px-2 py-3"><StatusBadge :status="account.is_active ? 'active' : 'inactive'" /></td>
                <td class="hidden px-2 py-3 text-right text-small text-muted dark:text-dark-muted md:table-cell">{{ formatDate(account.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <EmptyState v-else title="No user accounts yet" description="Create the first account to begin setting up EduCore." action-label="Create account" action-href="/users/create" />
      </BaseCard>

      <BaseCard title="Accounts by role" class="motion-safe:animate-section-in" :style="delay(5)">
        <template #description>Share of all accounts, active and inactive.</template>
        <div v-if="roleCounts.length">
          <PieChart :labels="roleChart.labels" :values="roleChart.values" :colors="roleColors" label="Accounts by role" value-label="Accounts" />
          <nav class="mt-4 flex flex-wrap items-center gap-2 border-t border-border-default pt-3 dark:border-dark-border" aria-label="Open the users of a role">
            <Link
              v-for="roleItem in roleCounts"
              :key="roleItem.slug"
              :href="`/users?role=${roleItem.slug}`"
              class="inline-flex items-center rounded-full border border-border-default bg-surface px-3 py-1 text-caption font-medium text-ink transition-colors hover:border-primary hover:bg-primary/5 hover:text-primary focus-visible:outline-2 focus-visible:outline-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:border-dark-primary dark:hover:bg-dark-primary/10"
            >
              {{ roleItem.name }}
            </Link>
          </nav>
        </div>
        <EmptyState v-else title="No roles assigned" description="Accounts will appear here as roles are assigned." />
      </BaseCard>
    </section>

    <section aria-labelledby="areas-heading" class="motion-safe:animate-section-in" :style="delay(7)">
      <h2 id="areas-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Management areas</h2>
      <p class="mt-1 text-small text-muted dark:text-dark-muted">Available tools, plus the modules planned for this workspace.</p>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <template v-for="area in areas" :key="area.title">
          <BaseCard v-if="area.href" :href="area.href" hoverable padding="sm" class="group">
            <div class="flex items-start gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary group-hover:text-white dark:bg-dark-primary/15 dark:text-dark-primary dark:group-hover:bg-dark-primary dark:group-hover:text-dark-bg"><component :is="area.icon" class="h-5 w-5" aria-hidden="true" /></span>
              <div class="min-w-0 flex-1">
                <h3 class="text-small font-semibold text-ink dark:text-dark-ink">{{ area.title }}</h3>
                <p class="mt-1 text-caption text-muted dark:text-dark-muted">{{ area.detail }}</p>
              </div>
            </div>
          </BaseCard>
          <div v-else class="flex items-start gap-3 rounded-xl border border-dashed border-border-muted p-4 dark:border-dark-border">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-background text-muted dark:bg-dark-surface-2 dark:text-dark-muted"><component :is="area.icon" class="h-5 w-5" aria-hidden="true" /></span>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-small font-semibold text-ink dark:text-dark-ink">{{ area.title }}</h3>
                <BaseBadge variant="muted" size="sm">Planned</BaseBadge>
              </div>
              <p class="mt-1 text-caption text-muted dark:text-dark-muted">{{ area.detail }}</p>
            </div>
          </div>
        </template>
      </div>
    </section>
  </div>
</template>
