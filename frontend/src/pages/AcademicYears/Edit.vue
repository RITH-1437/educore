<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseCard from '../../components/BaseCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'
import BaseBadge from '../../components/BaseBadge.vue'

const props = defineProps({
  academicYear: { type: Object, required: true },
  semesters: { type: Array, required: true },
})

const form = useForm({
  code: props.academicYear.code,
  name: props.academicYear.name,
  start_date: props.academicYear.start_date ?? '',
  end_date: props.academicYear.end_date ?? '',
})

const semesterForm = useForm({
  name: '',
  code: '',
  sequence: '',
  start_date: '',
  end_date: '',
  status: 'planned',
})

const semesterStatuses = [
  { value: 'planned', label: 'Planned' },
  { value: 'open', label: 'Open' },
]

const submit = () => form.put(`/academic-years/${props.academicYear.id}`, { preserveScroll: true })

const addSemester = () =>
  semesterForm.post(`/academic-years/${props.academicYear.id}/semesters`, {
    preserveScroll: true,
    onSuccess: () => semesterForm.reset(),
  })

const changeSemesterStatus = (semester, status) =>
  router.post(`/academic-years/${props.academicYear.id}/semesters/${semester.id}/status`, { status })

const { confirm } = useConfirm()

const deleteSemester = async (semester) => {
  if (await confirm({ title: 'Delete semester?', message: `Delete "${semester.name}"? This cannot be undone.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/academic-years/${props.academicYear.id}/semesters/${semester.id}`, {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <div class="space-y-6">
    <Head :title="`Edit ${academicYear.code}`" />

    <header class="flex items-end justify-between gap-4">
      <div>
        <h1 class="text-h1 font-display font-semibold text-ink dark:text-dark-ink">{{ academicYear.name }}</h1>
        <p class="mt-2 flex flex-wrap items-center gap-2 text-small text-muted dark:text-dark-muted">
          Code <span class="font-medium text-ink dark:text-dark-ink">{{ academicYear.code }}</span>
          <StatusBadge :status="academicYear.status" />
          <BaseBadge v-if="academicYear.is_current" variant="primary">Current year</BaseBadge>
        </p>
      </div>
      <Link href="/academic-years" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Back to calendar</Link>
    </header>

    <div v-if="flash?.error" class="rounded-lg border border-error/20 bg-error/5 px-4 py-3 text-small text-error" role="alert">
      {{ flash.error }}
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <BaseCard title="Academic year details" padding="lg">
        <form class="space-y-5" @submit.prevent="submit">
          <BaseInput v-model="form.code" name="code" label="Code" :error="form.errors.code" required />
          <BaseInput v-model="form.name" name="name" label="Name" :error="form.errors.name" required />
          <div class="grid gap-5 sm:grid-cols-2">
            <BaseInput v-model="form.start_date" name="start_date" label="Start date" type="date" :error="form.errors.start_date" required />
            <BaseInput v-model="form.end_date" name="end_date" label="End date" type="date" :error="form.errors.end_date" required />
          </div>
          <p class="text-small text-muted dark:text-dark-muted">
            Status changes are made from the list (planned → active → completed) so the transition rules apply.
          </p>
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
        </form>
      </BaseCard>

      <div class="space-y-6">
        <BaseCard title="Semesters" padding="lg">
          <template #description>
            Ordered by sequence; every course offering hangs from a semester.
          </template>

          <table class="mt-4 min-w-full divide-y divide-border-default text-small dark:divide-dark-border">
            <thead class="bg-background text-left text-caption font-semibold uppercase tracking-wide text-muted dark:bg-dark-surface-2 dark:text-dark-muted">
              <tr>
                <th class="px-3 py-2">#</th>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">Span</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-default dark:divide-dark-border">
              <tr v-for="semester in semesters" :key="semester.id">
                <td class="px-3 py-3 text-muted dark:text-dark-muted">{{ semester.sequence }}</td>
                <td class="px-3 py-3 font-medium text-ink dark:text-dark-ink">{{ semester.name }}</td>
                <td class="px-3 py-3 text-muted dark:text-dark-muted">
                  {{ semester.start_date ?? '—' }} → {{ semester.end_date ?? '—' }}
                </td>
                <td class="px-3 py-3"><StatusBadge :status="semester.status" /></td>
                <td class="px-3 py-3 text-right">
                  <button
                    v-if="semester.status === 'planned'"
                    type="button"
                    class="font-medium text-primary hover:underline dark:text-dark-primary"
                    @click="changeSemesterStatus(semester, 'open')"
                  >
                    Open
                  </button>
                  <button
                    v-else-if="semester.status === 'open'"
                    type="button"
                    class="font-medium text-warning hover:underline"
                    @click="changeSemesterStatus(semester, 'closed')"
                  >
                    Close
                  </button>
                  <button
                    type="button"
                    class="ml-3 font-medium text-error hover:underline"
                    @click="deleteSemester(semester)"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="semesters.length === 0">
                <td colspan="5" class="px-3 py-6 text-center text-muted dark:text-dark-muted">No semesters yet.</td>
              </tr>
            </tbody>
          </table>
        </BaseCard>

        <BaseCard title="Add a semester" padding="lg">
          <form class="space-y-4" @submit.prevent="addSemester">
            <div class="grid gap-4 sm:grid-cols-3">
              <BaseInput v-model="semesterForm.name" name="semester_name" label="Name" placeholder="Semester 3" :error="semesterForm.errors.name" required />
              <BaseInput v-model="semesterForm.code" name="semester_code" label="Code" placeholder="S3" :error="semesterForm.errors.code" required />
              <BaseInput v-model="semesterForm.sequence" name="sequence" label="Sequence" type="number" :error="semesterForm.errors.sequence" required />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <BaseInput v-model="semesterForm.start_date" name="semester_start_date" label="Start date" type="date" :error="semesterForm.errors.start_date" />
              <BaseInput v-model="semesterForm.end_date" name="semester_end_date" label="End date" type="date" :error="semesterForm.errors.end_date" />
            </div>
            <BaseSelect v-model="semesterForm.status" label="Initial status" :options="semesterStatuses" placeholder="Use planned" :error="semesterForm.errors.status" />
            <p class="text-caption text-muted dark:text-dark-muted">Dates must fall inside {{ academicYear.start_date }} → {{ academicYear.end_date }}.</p>
            <BaseButton type="submit" :loading="semesterForm.processing">Add semester</BaseButton>
          </form>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
