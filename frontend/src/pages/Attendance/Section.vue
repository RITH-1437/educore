<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import EmptyState from '../../components/EmptyState.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  section: { type: Object, required: true },
  date: { type: String, required: true },
  session: { type: Object, default: null },
  roster: { type: Array, default: () => [] },
  summary: { type: Array, default: () => [] },
  expectedDates: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  canRecord: { type: Boolean, default: false },
})

const { confirm } = useConfirm()
const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)
const editable = computed(() => props.canRecord && !props.section.locked && props.session?.status !== 'cancelled')

// Local working copy of the register; unmarked rows are not sent.
const marks = ref(Object.fromEntries(props.roster.map((row) => [row.enrollment_id, row.status])))
const form = useForm({ session_date: props.date, topic: props.session?.topic ?? '', records: [] })

const tone = { present: 'bg-success text-white', late: 'bg-warning text-primary-dark', absent: 'bg-error text-white', excused: 'bg-muted text-white' }
const counts = computed(() => props.statuses.map((status) => ({ status, count: Object.values(marks.value).filter((value) => value === status).length })))

const markAll = (status) => {
  for (const row of props.roster) marks.value[row.enrollment_id] = status
}
const changeDate = (value) => router.get(`/attendance/sections/${props.section.id}`, { date: value }, { preserveScroll: true })
const save = () =>
  form
    .transform((data) => ({
      ...data,
      records: Object.entries(marks.value).filter(([, status]) => status).map(([enrollment_id, status]) => ({ enrollment_id: Number(enrollment_id), status })),
    }))
    .post(`/attendance/sections/${props.section.id}`, { preserveScroll: true })

const toggleCancel = async () => {
  const cancelling = props.session.status !== 'cancelled'
  if (!cancelling || await confirm({ title: 'Cancel this class?', message: 'Records are kept but the class no longer counts toward attendance rates.', confirmLabel: 'Cancel class' })) {
    router.post(`/attendance-sessions/${props.session.id}/cancel`, { cancelled: cancelling }, { preserveScroll: true })
  }
}
</script>

<template>
  <Head :title="`Attendance ${section.course.code} ${section.code} - EduCore`" />
  <div class="space-y-6">
    <PageHeader eyebrow="Attendance" :title="`${section.course.code} · Section ${section.code}`" :description="`${section.course.name} · ${section.semester}`" />

    <p v-if="section.locked" class="text-small text-warning">The semester is completed; attendance is read-only.</p>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
      <BaseCard title="Class dates" padding="lg">
        <template #description>Scheduled meetings up to today, newest first.</template>
        <BaseInput :model-value="date" name="date" label="Or pick a date" type="date" @update:model-value="changeDate" />
        <ul class="mt-4 max-h-96 space-y-1 overflow-y-auto">
          <li v-for="item in expectedDates" :key="item.date">
            <button
              type="button"
              class="flex min-h-10 w-full items-center justify-between rounded-md px-3 text-small transition-colors hover:bg-background focus-visible:outline-2 focus-visible:outline-primary dark:hover:bg-dark-surface-2"
              :class="item.date === date ? 'bg-primary/10 font-semibold text-primary dark:bg-dark-primary/15 dark:text-dark-primary' : 'text-ink dark:text-dark-ink'"
              :aria-current="item.date === date ? 'date' : undefined"
              @click="changeDate(item.date)"
            >
              <span>{{ item.day }} {{ item.date }}</span>
              <StatusBadge v-if="item.recorded" :status="item.status === 'held' ? 'completed' : item.status" :label="item.status === 'held' ? 'Taken' : 'Cancelled'" />
              <span v-else class="text-caption text-muted dark:text-dark-muted">Not taken</span>
            </button>
          </li>
        </ul>
        <p v-if="!expectedDates.length" class="mt-4 text-small text-muted dark:text-dark-muted">No weekly schedule yet — pick any date in the semester.</p>
      </BaseCard>

      <BaseCard :title="`Register · ${date}`" padding="lg">
        <template #description>
          <span v-if="session?.status === 'cancelled'">This class was cancelled.</span>
          <span v-else-if="session">Saved — you can update any student.</span>
          <span v-else>Not taken yet. Unmarked students are not counted.</span>
        </template>
        <template v-if="canRecord && session && !section.locked" #actions>
          <BaseButton size="sm" variant="ghost" @click="toggleCancel">{{ session.status === 'cancelled' ? 'Restore class' : 'Cancel class' }}</BaseButton>
        </template>

        <EmptyState v-if="!roster.length" title="No students enrolled" description="Students appear here once they enroll in this section." />

        <template v-else>
          <div v-if="editable" class="mb-4 flex flex-wrap items-center gap-2 text-small">
            <span class="text-muted dark:text-dark-muted">Mark all:</span>
            <BaseButton v-for="status in statuses" :key="status" size="sm" variant="secondary" @click="markAll(status)">{{ label(status) }}</BaseButton>
          </div>

          <ul class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="row in roster" :key="row.enrollment_id" class="flex flex-wrap items-center justify-between gap-3 py-2">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ row.student.full_name }}</p>
                <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
              </div>
              <div v-if="editable" class="flex gap-1" role="radiogroup" :aria-label="`Attendance for ${row.student.full_name}`">
                <button
                  v-for="status in statuses"
                  :key="status"
                  type="button"
                  role="radio"
                  :aria-checked="marks[row.enrollment_id] === status"
                  class="min-h-9 rounded-md px-3 text-caption font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-primary"
                  :class="marks[row.enrollment_id] === status ? tone[status] : 'bg-background text-muted hover:text-ink dark:bg-dark-surface-2 dark:text-dark-muted'"
                  @click="marks[row.enrollment_id] = status"
                >
                  {{ label(status) }}
                </button>
              </div>
              <StatusBadge v-else-if="row.status" :status="row.status === 'present' ? 'active' : row.status === 'absent' ? 'failed' : 'pending'" :label="label(row.status)" />
              <span v-else class="text-caption text-muted dark:text-dark-muted">Unmarked</span>
            </li>
          </ul>

          <form v-if="editable" class="mt-5 flex flex-wrap items-end gap-3 border-t border-border-default pt-4 dark:border-dark-border" @submit.prevent="save">
            <BaseInput v-model="form.topic" name="topic" label="Topic (optional)" class="min-w-56 flex-1" :error="form.errors.topic" />
            <p class="text-caption text-muted dark:text-dark-muted"><template v-for="item in counts" :key="item.status">{{ label(item.status) }} {{ item.count }} · </template></p>
            <BaseButton type="submit" :loading="form.processing">Save attendance</BaseButton>
          </form>
          <ErrorAlert v-if="Object.keys(form.errors).length" class="mt-4" title="Could not save" :message="Object.values(form.errors)[0]" />
        </template>
      </BaseCard>
    </div>

    <BaseCard title="Attendance rates" padding="lg">
      <template #description>Present and late count as attended; excused, unmarked and cancelled classes are not counted.</template>
      <div class="-mx-2 overflow-x-auto">
        <table class="min-w-full">
          <caption class="sr-only">Attendance per student</caption>
          <thead>
            <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
              <th scope="col" class="px-2 py-2">Student</th>
              <th v-for="status in statuses" :key="status" scope="col" class="px-2 py-2 text-center">{{ label(status) }}</th>
              <th scope="col" class="px-2 py-2 text-right">Rate</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-default dark:divide-dark-border">
            <tr v-for="row in summary" :key="row.enrollment_id">
              <td class="px-2 py-2 text-small">{{ row.student.full_name }}</td>
              <td v-for="status in statuses" :key="status" class="px-2 py-2 text-center text-small tabular-nums">{{ row[status] }}</td>
              <td class="px-2 py-2 text-right text-small font-semibold tabular-nums" :class="row.rate !== null && row.rate < 75 ? 'text-error dark:text-red-300' : 'text-ink dark:text-dark-ink'">{{ row.rate === null ? '—' : `${row.rate}%` }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </BaseCard>
  </div>
</template>
