<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ArrowRight, Award, CalendarClock, GraduationCap, UserCheck } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import { examWhen, typeLabel } from '../../utils/exams'
import { gpa as formatGpa, percent } from '../../utils/grades'

const props = defineProps({
  /** Null when the account is not linked to a student profile yet. */
  dashboard: { type: Object, default: null },
  userName: { type: String, required: true },
})

const d = computed(() => props.dashboard)
const dueLabel = (iso) => new Date(iso).toLocaleString(undefined, { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
const rateTone = (rate) => (rate === null ? 'text-muted dark:text-dark-muted' : rate < 75 ? 'text-error' : 'text-ink dark:text-dark-ink')
</script>

<template>
  <Head title="Dashboard - EduCore" />
  <div class="space-y-6">
    <PageHeader
      eyebrow="Student dashboard"
      :title="d?.student.full_name ?? userName"
      :description="d ? [d.student.student_number, d.student.program?.name, d.semester?.name].filter(Boolean).join(' · ') : 'No student profile linked'"
    />

    <BaseCard v-if="!d">
      <EmptyState
        title="No student profile"
        description="This account is not linked to a student profile yet, so there are no courses, grades or documents to show. Ask the registrar or an administrator to link it."
        action-label="Open announcements"
        action-href="/announcements"
      />
    </BaseCard>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Cumulative GPA" :value="formatGpa(d.gpa.cumulative)" :icon="Award" href="/my-grades" :detail="d.gpa.latest_semester ? `${d.gpa.latest_semester.name}: ${formatGpa(d.gpa.latest_semester.gpa)}` : 'No approved grades yet'" />
        <StatCard label="Credits this semester" :value="d.credits.current" :icon="CalendarClock" tone="secondary" href="/registration" :detail="`${d.credits.courses} course${d.credits.courses === 1 ? '' : 's'}`" />
        <StatCard label="Attendance" :value="d.attendance.rate === null ? '—' : percent(d.attendance.rate)" :icon="UserCheck" :tone="d.attendance.rate !== null && d.attendance.rate < 75 ? 'warning' : 'success'" href="/my-attendance" detail="This semester" />
        <StatCard label="Credits earned" :value="d.credits.earned" :icon="GraduationCap" tone="muted" :detail="`of ${d.credits.attempted} attempted`" />
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Today's classes -->
        <BaseCard title="Today's classes">
          <template #actions><IconButton :icon="ArrowRight" href="/timetable" label="Open my timetable" /></template>
          <EmptyState v-if="!d.today.length" title="No classes today" description="Enjoy the break — your full week is in My timetable." />
          <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="entry in d.today" :key="entry.id" class="flex items-start justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ entry.course.code }} · {{ entry.course.name }}</p>
                <p class="text-caption text-muted dark:text-dark-muted">Section {{ entry.section.code }} · {{ entry.room.code }}<span v-if="entry.lecturer"> · {{ entry.lecturer }}</span></p>
              </div>
              <span class="shrink-0 text-small font-semibold tabular-nums text-ink dark:text-dark-ink">{{ entry.start_time }}–{{ entry.end_time }}</span>
            </li>
          </ul>
        </BaseCard>

        <!-- Upcoming assignments -->
        <BaseCard title="Assignments due">
          <template #actions><IconButton :icon="ArrowRight" href="/my-assignments" label="Open my assignments" /></template>
          <EmptyState v-if="!d.assignments.length" title="Nothing due" description="Published assignments you still need to submit appear here." />
          <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="assignment in d.assignments" :key="assignment.id" class="flex items-start justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ assignment.title }}</p>
                <p class="text-caption text-muted dark:text-dark-muted">{{ assignment.course.code }} · out of {{ assignment.max_score }}</p>
              </div>
              <BaseBadge variant="warning" size="sm">Due {{ dueLabel(assignment.due_at) }}</BaseBadge>
            </li>
          </ul>
        </BaseCard>

        <!-- Upcoming exams -->
        <BaseCard title="Upcoming exams">
          <template #actions><IconButton :icon="ArrowRight" href="/my-exams" label="Open my exams" /></template>
          <EmptyState v-if="!d.exams.length" title="No upcoming exams" description="Scheduled exams appear here." />
          <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="exam in d.exams" :key="exam.id" class="flex items-start justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ exam.course.code }} · {{ exam.title }}</p>
                <p class="text-caption text-muted dark:text-dark-muted">{{ examWhen(exam) }}</p>
              </div>
              <BaseBadge variant="muted" size="sm">{{ typeLabel(exam.exam_type) }}</BaseBadge>
            </li>
          </ul>
        </BaseCard>

        <!-- Recent grades -->
        <BaseCard title="Recent grades">
          <template #actions><IconButton :icon="ArrowRight" href="/my-grades" label="Open grades and GPA" /></template>
          <EmptyState v-if="!d.grades.length" title="No grades yet" description="Approved course grades appear here." />
          <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="grade in d.grades" :key="grade.id" class="flex items-start justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ grade.course.code }} · {{ grade.course.name }}</p>
                <p class="text-caption text-muted dark:text-dark-muted">{{ grade.semester }} · {{ grade.credits }} credits</p>
              </div>
              <span class="shrink-0 text-small font-semibold" :class="grade.grade_point > 0 ? 'text-ink dark:text-dark-ink' : 'text-error'">{{ grade.letter_grade }} <span class="font-normal text-muted dark:text-dark-muted">({{ formatGpa(grade.grade_point) }})</span></span>
            </li>
          </ul>
        </BaseCard>
      </div>

      <!-- Latest announcements (module 9.19) -->
      <BaseCard title="Announcements">
        <template #actions><IconButton :icon="ArrowRight" href="/announcements" label="All announcements" /></template>
        <EmptyState v-if="!d.announcements.length" title="No announcements" description="News for your program, classes and courses appears here." />
        <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
          <li v-for="item in d.announcements" :key="item.id" class="py-3">
            <p class="text-small font-medium text-ink dark:text-dark-ink">{{ item.title }}</p>
            <p class="text-caption text-muted dark:text-dark-muted">{{ item.audience }} · {{ dueLabel(item.published_at) }}</p>
          </li>
        </ul>
      </BaseCard>

      <!-- Attendance by course -->
      <BaseCard v-if="d.attendance.courses.length" title="Attendance by course">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <li v-for="row in d.attendance.courses" :key="`${row.course.code}-${row.section}`" class="flex items-center justify-between gap-3 rounded-lg border border-border-default px-4 py-3 dark:border-dark-border">
            <span class="min-w-0 truncate text-small text-ink dark:text-dark-ink">{{ row.course.code }} · {{ row.section }}</span>
            <span class="text-small font-semibold tabular-nums" :class="rateTone(row.rate)">{{ percent(row.rate) }}</span>
          </li>
        </ul>
        <p class="mt-3 text-caption text-muted dark:text-dark-muted">Below 75% is highlighted. Excused absences are not counted.</p>
      </BaseCard>
    </template>
  </div>
</template>
