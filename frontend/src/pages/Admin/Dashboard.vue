<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  ArrowRight,
  BookOpen,
  CalendarDays,
  CircleCheck,
  GraduationCap,
  Landmark,
  Presentation,
  TriangleAlert,
  Users,
} from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  roleCounts: { type: Array, default: () => [] },
  currentAcademicYear: { type: Object, default: null },
  recentUsers: { type: Array, default: () => [] },
})

const page = usePage()
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'administrator')

const metrics = computed(() => [
  { label: 'Total accounts', value: props.stats.total_users, detail: `${props.stats.active_users} active`, icon: Users, href: '/users', tone: 'primary' },
  { label: 'Academic years', value: props.stats.total_academic_years, detail: `${props.stats.active_academic_years} active`, icon: CalendarDays, href: '/academic-years', tone: 'secondary' },
  { label: 'Faculties', value: props.stats.total_faculties, detail: 'Institution structure', icon: Landmark, href: '/faculties', tone: 'success' },
  { label: 'Programs', value: props.stats.total_programs, detail: `${props.stats.total_semesters} semesters`, icon: BookOpen, href: '/programs', tone: 'warning' },
])

// Only real, derivable signals — nothing is shown when everything is in order.
const attention = computed(() => {
  const items = []
  const inactive = props.stats.total_users - props.stats.active_users
  if (!props.currentAcademicYear) items.push({ text: 'No current academic year is set.', href: '/academic-years', action: 'Set one' })
  if (inactive > 0) items.push({ text: `${inactive} ${inactive === 1 ? 'account is' : 'accounts are'} inactive.`, href: '/users', action: 'Review accounts' })
  return items
})

const roleShare = (total) => (props.stats.total_users ? Math.max(4, Math.round((total / props.stats.total_users) * 100)) : 0)

const areas = [
  { title: 'Users & roles', detail: 'Create accounts and assign platform roles.', href: '/users', icon: Users },
  { title: 'Academic calendar', detail: 'Years, semesters, and status transitions.', href: '/academic-years', icon: CalendarDays },
  { title: 'Faculties & departments', detail: 'Organize the university structure.', href: '/faculties', icon: Landmark },
  { title: 'Programs', detail: 'Degree tracks offered by each department.', href: '/programs', icon: BookOpen },
  { title: 'Courses', detail: 'Catalog, prerequisites and program curricula.', href: '/courses', icon: GraduationCap },
  { title: 'Lecturers', detail: 'Teaching staff profiles and accounts.', href: '/lecturers', icon: Presentation },
  { title: 'Students', detail: 'Profiles, status and program history.', href: '/students', icon: Users },
]

const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '—')
const delay = (step) => ({ animationDelay: `${step * 60}ms` })
</script>

<template>
  <Head title="Admin dashboard - EduCore" />

  <div class="space-y-8">
    <PageHeader eyebrow="Platform overview" :title="`Welcome back, ${firstName}`" description="A snapshot of accounts, the academic calendar, and what needs your attention.">
      <template #actions>
        <BaseButton href="/academic-years/create" variant="secondary"><CalendarDays class="h-4 w-4" aria-hidden="true" /> New academic year</BaseButton>
        <BaseButton href="/users/create"><Users class="h-4 w-4" aria-hidden="true" /> Create account</BaseButton>
      </template>
    </PageHeader>

    <section v-if="attention.length" class="rounded-xl border border-warning/30 bg-warning/5 p-4 motion-safe:animate-section-in sm:p-5" aria-label="Needs attention">
      <h2 class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink"><TriangleAlert class="h-4 w-4 text-warning" aria-hidden="true" /> Needs attention</h2>
      <ul class="mt-3 divide-y divide-warning/20">
        <li v-for="item in attention" :key="item.text" class="flex flex-wrap items-center justify-between gap-2 py-2 first:pt-0 last:pb-0">
          <span class="text-small text-ink dark:text-dark-ink">{{ item.text }}</span>
          <Link :href="item.href" class="inline-flex min-h-8 items-center gap-1 text-small font-semibold text-primary hover:underline dark:text-dark-primary">{{ item.action }} <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link>
        </li>
      </ul>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Platform metrics">
      <StatCard v-for="(metric, index) in metrics" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index)" />
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
      <BaseCard title="Recently created accounts" padding="lg" class="min-w-0 motion-safe:animate-section-in" :style="delay(4)">
        <template #description>Latest platform accounts with their role and status.</template>
        <template #actions><Link href="/users" class="inline-flex min-h-8 items-center gap-1 whitespace-nowrap text-small font-semibold text-primary hover:underline dark:text-dark-primary">View all <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link></template>
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

      <div class="space-y-6">
        <BaseCard title="Current academic year" class="motion-safe:animate-section-in" :style="delay(5)">
          <template #description>Institution-wide calendar context.</template>
          <div v-if="currentAcademicYear">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ currentAcademicYear.name }}</p>
                <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ currentAcademicYear.code }} · {{ currentAcademicYear.start_date }} – {{ currentAcademicYear.end_date }}</p>
              </div>
              <StatusBadge :status="currentAcademicYear.status" />
            </div>
            <ul class="mt-4 space-y-3">
              <li v-for="semester in currentAcademicYear.semesters" :key="semester.id" class="flex items-center justify-between gap-3 border-t border-border-default pt-3 dark:border-dark-border">
                <div>
                  <p class="text-small font-medium text-ink dark:text-dark-ink">{{ semester.sequence }}. {{ semester.name }}</p>
                  <p class="text-caption text-muted dark:text-dark-muted">{{ semester.start_date || 'Dates not set' }}<template v-if="semester.end_date"> – {{ semester.end_date }}</template></p>
                </div>
                <StatusBadge :status="semester.status" />
              </li>
            </ul>
            <p v-if="currentAcademicYear.semesters.length === 0" class="mt-4 text-small text-muted dark:text-dark-muted">No semesters are configured yet.</p>
            <Link href="/academic-years" class="mt-4 inline-flex min-h-8 items-center gap-1 text-small font-semibold text-primary hover:underline dark:text-dark-primary">Manage academic calendar <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link>
          </div>
          <EmptyState v-else title="No current academic year" description="Create and activate an academic year to set the current calendar." action-label="Create academic year" action-href="/academic-years/create" />
        </BaseCard>

        <BaseCard title="Accounts by role" class="motion-safe:animate-section-in" :style="delay(6)">
          <template #description>Share of all accounts, active and inactive.</template>
          <ul v-if="roleCounts.length" class="space-y-4">
            <li v-for="roleItem in roleCounts" :key="roleItem.slug">
              <div class="flex items-center justify-between gap-4 text-small">
                <span class="text-ink dark:text-dark-ink">{{ roleItem.name }}</span>
                <span class="font-semibold tabular-nums text-ink dark:text-dark-ink">{{ roleItem.total }}</span>
              </div>
              <div class="mt-2 h-2 overflow-hidden rounded-pill bg-background dark:bg-dark-surface-2" role="presentation">
                <div class="h-full rounded-pill bg-primary transition-[width] duration-500 ease-out dark:bg-dark-primary motion-reduce:transition-none" :style="{ width: `${roleShare(roleItem.total)}%` }" />
              </div>
            </li>
          </ul>
          <EmptyState v-else title="No roles assigned" description="Accounts will appear here as roles are assigned." />
        </BaseCard>
      </div>
    </section>

    <section aria-labelledby="areas-heading" class="motion-safe:animate-section-in" :style="delay(7)">
      <h2 id="areas-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Management areas</h2>
      <p class="mt-1 text-small text-muted dark:text-dark-muted">Available tools, plus the modules planned for this workspace.</p>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <template v-for="area in areas" :key="area.title">
          <BaseCard v-if="area.href" :href="area.href" hoverable padding="sm" class="group">
            <div class="flex items-start gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary"><component :is="area.icon" class="h-5 w-5" aria-hidden="true" /></span>
              <div class="min-w-0 flex-1">
                <h3 class="text-small font-semibold text-ink dark:text-dark-ink">{{ area.title }}</h3>
                <p class="mt-1 text-caption text-muted dark:text-dark-muted">{{ area.detail }}</p>
              </div>
              <ArrowRight class="h-4 w-4 shrink-0 text-muted transition-transform duration-150 group-hover:translate-x-0.5 dark:text-dark-muted motion-reduce:transition-none" aria-hidden="true" />
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

    <p class="flex items-center gap-2 text-caption text-muted dark:text-dark-muted">
      <CircleCheck class="h-4 w-4 shrink-0 text-success" aria-hidden="true" />
      Metrics are current totals; trends will appear once analytics is available. Actions are enforced by server-side role checks.
    </p>
  </div>
</template>
