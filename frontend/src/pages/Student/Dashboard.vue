<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Award, CalendarClock, GraduationCap, UserCheck } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import { examWhen, typeLabel } from '../../utils/exams'
import { gpa as formatGpa, percent } from '../../utils/grades'

const props = defineProps({
  dashboard: { type: Object, required: true },
  userName: { type: String, required: true },
})

const d = computed(() => props.dashboard)
const dueLabel = (iso) => new Date(iso).toLocaleString(undefined, { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
const rateTone = (rate) => (rate === null ? 'text-muted dark:text-dark-muted' : rate < 75 ? 'text-error' : 'text-ink dark:text-dark-ink')
const linkClass = 'text-small font-medium text-primary hover:underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-primary'
</script>

<template>
  <Head title="Dashboard - EduCore" />
  <div class="space-y-6">
    <PageHeader
      eyebrow="Student dashboard"
      :title="`Welcome, ${dashboard.student.full_name}`"
      :description="[dashboard.student.student_number, dashboard.student.program?.name, dashboard.semester?.name].filter(Boolean).join(' · ')"
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard label="Cumulative GPA" :value="formatGpa(d.gpa.cumulative)" :icon="Award" href="/my-grades" :detail="d.gpa.latest_semester ? `${d.gpa.latest_semester.name}: ${formatGpa(d.gpa.latest_semester.gpa)}` : 'No approved grades yet'" />
      <StatCard label="Credits this semester" :value="d.credits.current" :icon="CalendarClock" tone="secondary" href="/registration" :detail="`${d.credits.courses} course${d.credits.courses === 1 ? '' : 's'}`" />
      <StatCard label="Attendance" :value="d.attendance.rate === null ? '—' : percent(d.attendance.rate)" :icon="UserCheck" :tone="d.attendance.rate !== null && d.attendance.rate < 75 ? 'warning' : 'success'" href="/my-attendance" detail="This semester" />
      <StatCard label="Credits earned" :value="d.credits.earned" :icon="GraduationCap" tone="muted" :detail="`of ${d.credits.attempted} attempted`" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Today's classes -->
      <BaseCard title="Today's classes">
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
        <Link href="/timetable" :class="['mt-3 inline-block', linkClass]">My timetable</Link>
      </BaseCard>

      <!-- Upcoming assignments -->
      <BaseCard title="Assignments due">
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
        <Link href="/my-assignments" :class="['mt-3 inline-block', linkClass]">My assignments</Link>
      </BaseCard>

      <!-- Upcoming exams -->
      <BaseCard title="Upcoming exams">
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
        <Link href="/my-exams" :class="['mt-3 inline-block', linkClass]">My exams</Link>
      </BaseCard>

      <!-- Recent grades -->
      <BaseCard title="Recent grades">
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
        <Link href="/my-grades" :class="['mt-3 inline-block', linkClass]">Grades &amp; GPA</Link>
      </BaseCard>
    </div>

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
  </div>
</template>
