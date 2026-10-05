<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import {
  Award,
  BookOpen,
  Building2,
  Calendar,
  Camera,
  GraduationCap,
  KeyRound,
  Link2,
  Mail,
  MapPin,
  Phone,
  ShieldCheck,
  Trash2,
  Upload,
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

const avatarMode = ref('device')
const fileInput = ref(null)
const selectedFile = ref(null)
const previewUrl = ref(null)
const isRemoved = ref(false)

const form = useForm({
  name: props.profile.name ?? '',
  phone: props.profile.phone ?? '',
  address: props.profile.student?.address ?? '',
  emergency_contact_name: props.profile.student?.emergency_contact_name ?? '',
  emergency_contact_phone: props.profile.student?.emergency_contact_phone ?? '',
  specialization: props.profile.lecturer?.specialization ?? '',
  avatar: null,
  avatar_url:
    props.profile.avatar_key && (props.profile.avatar_key.startsWith('http://') || props.profile.avatar_key.startsWith('https://'))
      ? props.profile.avatar_key
      : '',
  remove_avatar: false,
})

const onFileSelected = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  selectedFile.value = file
  form.avatar = file
  form.remove_avatar = false
  isRemoved.value = false

  if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = URL.createObjectURL(file)
}

const onUrlChanged = () => {
  if (form.avatar_url && (form.avatar_url.startsWith('http://') || form.avatar_url.startsWith('https://'))) {
    previewUrl.value = form.avatar_url
    form.remove_avatar = false
    isRemoved.value = false
  } else if (!form.avatar_url) {
    previewUrl.value = null
  }
}

const removeAvatar = () => {
  selectedFile.value = null
  form.avatar = null
  form.avatar_url = ''
  form.remove_avatar = true
  isRemoved.value = true
  if (fileInput.value) fileInput.value.value = ''
  if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = null
}

const currentDisplayAvatar = computed(() => {
  if (isRemoved.value) return null
  if (previewUrl.value) return previewUrl.value
  return props.profile.avatar_url ?? null
})

const submit = () => {
  form.post('/account/profile', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isRemoved.value = false
      selectedFile.value = null
      if (fileInput.value) fileInput.value.value = ''
    },
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
      description="View your institutional affiliation, credentials, and manage your avatar and contact information."
    />

    <!-- User identity card -->
    <BaseCard padding="lg">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <div class="relative shrink-0">
            <img
              v-if="currentDisplayAvatar"
              :src="currentDisplayAvatar"
              :alt="u.name"
              class="h-16 w-16 rounded-pill object-cover ring-2 ring-primary/20 shadow-xs dark:ring-dark-primary/30"
            />
            <div
              v-else
              class="flex h-16 w-16 items-center justify-center rounded-pill bg-primary text-h3 font-bold text-white shadow-sm dark:bg-dark-primary dark:text-dark-bg"
              aria-hidden="true"
            >
              {{ initial }}
            </div>
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

    <!-- Profile Photo & Contact Information Edit Form -->
    <BaseCard title="Profile Photo & Contact Details" padding="lg">
      <template #description>
        Personalize your avatar from your local device or a public image URL, and keep your contact details updated.
      </template>

      <form class="space-y-6" @submit.prevent="submit">
        <!-- Avatar Customization Block -->
        <div class="rounded-lg border border-border-default bg-surface/50 p-4 dark:border-dark-border dark:bg-dark-surface/50 space-y-4">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
              <div class="relative shrink-0">
                <img
                  v-if="currentDisplayAvatar"
                  :src="currentDisplayAvatar"
                  :alt="form.name || u.name"
                  class="h-16 w-16 rounded-pill object-cover ring-2 ring-primary shadow-xs dark:ring-dark-primary"
                />
                <div
                  v-else
                  class="flex h-16 w-16 items-center justify-center rounded-pill bg-primary text-h4 font-bold text-white shadow-xs dark:bg-dark-primary dark:text-dark-bg"
                >
                  {{ initial }}
                </div>
              </div>
              <div>
                <p class="text-small font-semibold text-ink dark:text-dark-ink">Avatar Photo</p>
                <p class="text-caption text-muted dark:text-dark-muted">
                  Upload an image from your device or paste a web URL.
                </p>
              </div>
            </div>

            <!-- Avatar Source Toggle Pills -->
            <div class="inline-flex rounded-md p-1 bg-surface-2 dark:bg-dark-surface-2 border border-border-default dark:border-dark-border" role="tablist">
              <button
                type="button"
                role="tab"
                :aria-selected="avatarMode === 'device'"
                class="inline-flex items-center gap-1.5 rounded px-3 py-1 text-caption font-medium transition-colors"
                :class="avatarMode === 'device' ? 'bg-primary text-white shadow-xs dark:bg-dark-primary dark:text-dark-bg' : 'text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink'"
                @click="avatarMode = 'device'"
              >
                <Upload class="h-3.5 w-3.5" aria-hidden="true" />
                From device
              </button>
              <button
                type="button"
                role="tab"
                :aria-selected="avatarMode === 'url'"
                class="inline-flex items-center gap-1.5 rounded px-3 py-1 text-caption font-medium transition-colors"
                :class="avatarMode === 'url' ? 'bg-primary text-white shadow-xs dark:bg-dark-primary dark:text-dark-bg' : 'text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink'"
                @click="avatarMode = 'url'"
              >
                <Link2 class="h-3.5 w-3.5" aria-hidden="true" />
                From URL
              </button>
            </div>
          </div>

          <!-- Mode: Device upload -->
          <div v-if="avatarMode === 'device'" class="flex flex-wrap items-center gap-3 pt-2">
            <input
              ref="fileInput"
              type="file"
              accept="image/png,image/jpeg,image/jpg,image/webp"
              class="hidden"
              @change="onFileSelected"
            />
            <BaseButton
              type="button"
              variant="secondary"
              size="sm"
              @click="fileInput?.click()"
            >
              <Camera class="h-4 w-4 mr-1.5" aria-hidden="true" />
              Choose image from device
            </BaseButton>

            <span v-if="selectedFile" class="text-caption font-medium text-ink dark:text-dark-ink">
              {{ selectedFile.name }} ({{ Math.round(selectedFile.size / 1024) }} KB)
            </span>
            <span v-else class="text-caption text-muted dark:text-dark-muted">
              Accepts JPG, PNG, or WebP up to 2 MB
            </span>

            <button
              v-if="currentDisplayAvatar"
              type="button"
              class="ml-auto inline-flex items-center gap-1 text-caption font-medium text-error hover:underline dark:text-red-400"
              @click="removeAvatar"
            >
              <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
              Remove photo
            </button>
          </div>

          <!-- Mode: URL input -->
          <div v-else class="space-y-2 pt-2">
            <div class="flex items-center gap-3">
              <div class="flex-1">
                <BaseInput
                  v-model="form.avatar_url"
                  name="avatar_url"
                  label="Direct image URL"
                  placeholder="https://example.com/avatar.jpg"
                  hint="Provide a direct, public HTTPS image link."
                  :error="form.errors.avatar_url"
                  @input="onUrlChanged"
                />
              </div>
              <button
                v-if="currentDisplayAvatar"
                type="button"
                class="mt-6 inline-flex items-center gap-1 text-caption font-medium text-error hover:underline dark:text-red-400"
                @click="removeAvatar"
              >
                <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
                Remove
              </button>
            </div>
          </div>

          <p v-if="form.errors.avatar" class="text-caption text-error">
            {{ form.errors.avatar }}
          </p>
        </div>

        <!-- Name and Phone -->
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
