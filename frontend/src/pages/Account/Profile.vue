<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import {
  Award,
  BookOpen,
  Building2,
  Calendar,
  GraduationCap,
  KeyRound,
  Mail,
  MapPin,
  Phone,
  ShieldCheck,
  UserCheck,
  UserRound,
} from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import { gpa as formatGpa } from '../../utils/grades'

const props = defineProps({
  profile: { type: Object, required: true },
})

const u = computed(() => props.profile)
const student = computed(() => props.profile.student)
const lecturer = computed(() => props.profile.lecturer)

const initial = computed(() => u.value.name?.slice(0, 1)?.toUpperCase() ?? 'U')

const form = useForm({
  name: props.profile.name ?? '',
  phone: props.profile.phone ?? '',
  address: props.profile.student?.address ?? '',
  emergency_contact_name: props.profile.student?.emergency_contact_name ?? '',
  emergency_contact_phone: props.profile.student?.emergency_contact_phone ?? '',
  specialization: props.profile.lecturer?.specialization ?? '',
})

const submit = () => {
  form.put('/account/profile', {
    preserveScroll: true,
  })
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<template>
  <Head title="Profile - EduCore" />

  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader
      eyebrow="Account"
      title="My Profile"
      description="View your institutional affiliation, credentials, and manage your contact information."
    />

    <!-- User identity card -->
    <BaseCard padding="lg">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <div
            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-pill bg-primary text-h3 font-bold text-white shadow-sm dark:bg-dark-primary dark:text-dark-bg"
            aria-hidden="true"
          >
            {{ initial }}
          </div>
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-h4 font-bold text-ink dark:text-dark-ink">{{ u.name }}</h2>
              <BaseBadge variant="primary" size="sm">{{ u.role?.name }}</BaseBadge>
              <BaseBadge v-if="u.is_active" variant="success" size="sm">Active</BaseBadge>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-small text-muted dark:text-dark-muted">
              <span class="inline-flex items-center gap-1.5">
                <Mail class="h-4 w-4" aria-hidden="true" />
                {{ u.email }}
              </span>
              <span v-if="u.department" class="inline-flex items-center gap-1.5">
                <Building2 class="h-4 w-4" aria-hidden="true" />
                {{ u.department.name }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 sm:shrink-0">
          <Link
            href="/account/password"
            class="inline-flex min-h-10 items-center gap-2 rounded-md border border-border-default bg-surface px-3 py-2 text-small font-medium text-ink transition-colors duration-150 hover:bg-muted-light/50 focus-visible:outline-2 focus-visible:outline-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2"
          >
            <KeyRound class="h-4 w-4" aria-hidden="true" />
            Change password
          </Link>
          <Link
            href="/notifications"
            class="inline-flex min-h-10 items-center gap-2 rounded-md border border-border-default bg-surface px-3 py-2 text-small font-medium text-ink transition-colors duration-150 hover:bg-muted-light/50 focus-visible:outline-2 focus-visible:outline-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2"
          >
            <ShieldCheck class="h-4 w-4" aria-hidden="true" />
            Security & Alerts
          </Link>
        </div>
      </div>
    </BaseCard>

    <!-- Student Academic Overview -->
    <template v-if="student">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          label="Cumulative GPA"
          :value="formatGpa(student.academic_summary?.cumulative_gpa)"
          :icon="Award"
          href="/my-grades"
          detail="Official transcript"
        />
        <StatCard
          label="Credits earned"
          :value="student.academic_summary?.earned_credits ?? 0"
          :icon="GraduationCap"
          tone="secondary"
          :detail="`of ${student.academic_summary?.attempted_credits ?? 0} attempted`"
        />
        <StatCard
          label="Active courses"
          :value="student.academic_summary?.current_courses_count ?? 0"
          :icon="BookOpen"
          href="/registration"
          detail="Current semester"
        />
        <StatCard
          label="Student status"
          :value="student.status.toUpperCase()"
          :icon="UserCheck"
          tone="success"
          detail="Academic status"
        />
      </div>

      <BaseCard title="Academic Affiliation" padding="lg">
        <template #description>Official registration details recorded by the University Registrar.</template>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Student ID</dt>
            <dd class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">{{ student.student_number }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Program</dt>
            <dd class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">
              {{ student.program ? `${student.program.code} - ${student.program.name}` : 'Not assigned' }}
            </dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Department</dt>
            <dd class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">
              {{ u.department?.name ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Enrollment Date</dt>
            <dd class="mt-1 text-small text-ink dark:text-dark-ink">{{ formatDate(student.enrollment_date) }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Gender</dt>
            <dd class="mt-1 text-small capitalize text-ink dark:text-dark-ink">{{ student.gender ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">National ID</dt>
            <dd class="mt-1 text-small text-ink dark:text-dark-ink">{{ student.national_id ?? '—' }}</dd>
          </div>
        </dl>
      </BaseCard>
    </template>

    <!-- Lecturer Academic Overview -->
    <template v-if="lecturer">
      <div class="grid gap-4 sm:grid-cols-2">
        <StatCard
          label="Staff ID"
          :value="lecturer.staff_number"
          :icon="UserRound"
          detail="Official employment identifier"
        />
        <StatCard
          label="Teaching sections"
          :value="lecturer.teaching_summary?.active_sections_count ?? 0"
          :icon="BookOpen"
          href="/dashboard"
          detail="Current sections assigned"
        />
      </div>

      <BaseCard title="Faculty & Position Details" padding="lg">
        <template #description>Faculty appointment and employment records.</template>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Academic Title</dt>
            <dd class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">{{ lecturer.title ?? 'Lecturer' }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Position</dt>
            <dd class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">{{ lecturer.position ?? 'Faculty Member' }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Department</dt>
            <dd class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">{{ u.department?.name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Employment Type</dt>
            <dd class="mt-1 text-small capitalize text-ink dark:text-dark-ink">{{ lecturer.employment_type?.replace('_', ' ') }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Specialization</dt>
            <dd class="mt-1 text-small text-ink dark:text-dark-ink">{{ lecturer.specialization ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-caption font-medium text-muted dark:text-dark-muted">Faculty Status</dt>
            <dd class="mt-1 text-small text-ink dark:text-dark-ink">
              <BaseBadge :variant="lecturer.is_active ? 'success' : 'muted'" size="sm">
                {{ lecturer.is_active ? 'Active Faculty' : 'Inactive' }}
              </BaseBadge>
            </dd>
          </div>
        </dl>
      </BaseCard>
    </template>

    <!-- Contact & Personal Information Edit Form -->
    <BaseCard title="Contact Information" padding="lg">
      <template #description>
        Update your personal contact information. Official academic records must be changed via the registrar.
      </template>

      <form class="space-y-5" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <BaseInput
            v-model="form.name"
            name="name"
            label="Full name"
            required
            :error="form.errors.name"
          />
          <BaseInput
            v-model="form.phone"
            name="phone"
            label="Phone number"
            type="tel"
            placeholder="+855 12 345 678"
            :error="form.errors.phone"
          />
        </div>

        <template v-if="student">
          <BaseInput
            v-model="form.address"
            name="address"
            label="Residential address"
            placeholder="Street address, city"
            :error="form.errors.address"
          />

          <div class="grid gap-4 sm:grid-cols-2">
            <BaseInput
              v-model="form.emergency_contact_name"
              name="emergency_contact_name"
              label="Emergency contact person"
              placeholder="Full name of contact"
              :error="form.errors.emergency_contact_name"
            />
            <BaseInput
              v-model="form.emergency_contact_phone"
              name="emergency_contact_phone"
              label="Emergency contact phone"
              type="tel"
              placeholder="+855 12 345 678"
              :error="form.errors.emergency_contact_phone"
            />
          </div>
        </template>

        <template v-if="lecturer">
          <BaseInput
            v-model="form.specialization"
            name="specialization"
            label="Academic specialization / Research area"
            placeholder="e.g. Distributed Systems, Machine Learning"
            :error="form.errors.specialization"
          />
        </template>

        <div class="flex items-center justify-end gap-3 pt-2 border-t border-border-default dark:border-dark-border">
          <span v-if="form.recentlySuccessful" class="text-small font-medium text-emerald-600 dark:text-emerald-400">
            Changes saved!
          </span>
          <BaseButton type="submit" :loading="form.processing">
            Save changes
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </div>
</template>
