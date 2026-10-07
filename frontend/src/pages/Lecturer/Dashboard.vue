<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArrowRight, Award, ClipboardList, FileCheck, UserCheck } from '@lucide/vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseTable from '../../components/BaseTable.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { examWhen, typeLabel } from '../../utils/exams'
import { gradeStatus, gradeStatusLabel } from '../../utils/grades'

const props = defineProps({
  // Null when the account is not linked to a lecturer profile.
  dashboard: { type: Object, default: null },
  userName: { type: String, required: true },
})

const d = computed(() => props.dashboard)

// Teaching work at a glance; the Teaching page (/attendance) holds every section's tools.
const metrics = computed(() => (!d.value ? [] : [
  { label: 'Classes today', value: d.value.counts.classes_today, detail: 'In your timetable', href: '/timetable' },
  { label: 'Registers to take', value: d.value.counts.registers_to_take, detail: 'Past classes without attendance', href: '/attendance' },
  { label: 'Submissions to grade', value: d.value.counts.submissions_to_grade, detail: 'Submitted or late, not graded', href: '/attendance' },
  { label: 'Upcoming exams', value: d.value.counts.upcoming_exams, detail: 'Scheduled from today', href: '/attendance' },
]))

const columns = [
  { key: 'section', label: 'Section' },
  { key: 'students', label: 'Students', align: 'right' },
  { key: 'registers_to_take', label: 'Registers to take', align: 'right' },
  { key: 'submissions_to_grade', label: 'To grade', align: 'right' },
  { key: 'grades', label: 'Grade sheet' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const sheetLabel = (state) => ({ not_started: 'Not started', no_students: 'No students' })[state] ?? gradeStatusLabel(state)
const countClass = (value) => (value > 0 ? 'font-semibold text-ink dark:text-dark-ink' : 'text-muted dark:text-dark-muted')
const name = (row) => `${row.course.code} ${row.code}`
const delay = (step) => ({ animationDelay: `${step * 60}ms` })
</script>

<template>
  <Head title="Dashboard - EduCore" />
  <div class="space-y-8">
    <PageHeader
      eyebrow="Lecturer dashboard"
      :title="userName"
      :description="d ? [d.semester?.name, `${d.sections.length} ${d.sections.length === 1 ? 'section' : 'sections'}`].filter(Boolean).join(' · ') : 'No lecturer profile linked'"
    />

    <BaseCard v-if="!d">
      <EmptyState title="No lecturer profile" description="This account is not linked to a lecturer profile yet, so there is no teaching data. Ask an administrator to link it." />
    </BaseCard>

    <template v-else>
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Teaching at a glance">
        <StatCard v-for="(metric, index) in metrics" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index)" />
      </section>

      <div class="grid gap-6 lg:grid-cols-2">
        <BaseCard title="Today's classes">
          <template #actions><IconButton :icon="ArrowRight" href="/timetable" label="Open my timetable" /></template>
          <EmptyState v-if="!d.today.length" title="No classes today" description="Your full week is in My timetable." />
          <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="entry in d.today" :key="entry.id" class="flex items-center justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ entry.course.code }} · {{ entry.course.name }}</p>
                <p class="text-caption text-muted dark:text-dark-muted"><span class="tabular-nums">{{ entry.start_time }}–{{ entry.end_time }}</span> · Section {{ entry.section.code }} · {{ entry.room.code }}</p>
              </div>
              <IconButton :icon="UserCheck" :href="`/attendance/sections/${entry.section.id}`" variant="primary" :label="`Take attendance for ${entry.course.code} ${entry.section.code}`" />
            </li>
          </ul>
        </BaseCard>

        <BaseCard title="Upcoming exams">
          <EmptyState v-if="!d.exams.length" title="No upcoming exams" description="Exams you schedule in your sections appear here." />
          <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="exam in d.exams" :key="exam.id" class="flex items-center justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ exam.course.code }} {{ exam.section_code }} · {{ exam.title }}</p>
                <p class="text-caption text-muted dark:text-dark-muted">{{ examWhen(exam) }}</p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <BaseBadge :variant="exam.is_published ? 'muted' : 'warning'" size="sm">{{ exam.is_published ? typeLabel(exam.exam_type) : 'Not published' }}</BaseBadge>
                <IconButton :icon="FileCheck" :href="`/exams/sections/${exam.section_id}`" :label="`Open the exams of ${exam.course.code} ${exam.section_code}`" />
              </div>
            </li>
          </ul>
        </BaseCard>
      </div>

      <section class="space-y-4" aria-labelledby="sections-heading">
        <div>
          <h2 id="sections-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Your sections</h2>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ d.semester ? `Sections you teach in ${d.semester.name}.` : 'Sections appear once a semester exists.' }}</p>
        </div>
        <BaseTable :columns="columns" :rows="d.sections" caption="Your sections" empty-title="No sections this semester" empty-description="Sections appear here once an administrator assigns you to them.">
          <template #cell-section="{ row }">
            <p class="font-medium">{{ row.course.code }} · {{ row.code }}</p>
            <p class="text-caption text-muted dark:text-dark-muted">{{ row.course.name }}<span v-if="row.role !== 'primary'"> · {{ row.role }}</span></p>
          </template>
          <template #cell-registers_to_take="{ row }"><span class="tabular-nums" :class="countClass(row.registers_to_take)">{{ row.registers_to_take }}</span></template>
          <template #cell-submissions_to_grade="{ row }"><span class="tabular-nums" :class="countClass(row.submissions_to_grade)">{{ row.submissions_to_grade }}</span></template>
          <template #cell-grades="{ row }"><StatusBadge :status="gradeStatus(row.grades)" :label="sheetLabel(row.grades)" /></template>
          <template #cell-actions="{ row }">
            <div class="flex justify-end gap-1">
              <IconButton :icon="UserCheck" :href="`/attendance/sections/${row.id}`" variant="primary" :label="`Take attendance for ${name(row)}`" />
              <IconButton :icon="ClipboardList" :href="`/coursework/sections/${row.id}`" :label="`Assignments of ${name(row)}`" />
              <IconButton :icon="FileCheck" :href="`/exams/sections/${row.id}`" :label="`Exams of ${name(row)}`" />
              <IconButton :icon="Award" :href="`/grades/sections/${row.id}`" :label="`Grade sheet of ${name(row)}`" />
            </div>
          </template>
        </BaseTable>
      </section>
    </template>
  </div>
</template>
