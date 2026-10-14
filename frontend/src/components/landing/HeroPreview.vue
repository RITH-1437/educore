<script setup>
import { BellRing, CalendarClock, CircleCheck, ClipboardList, Send } from '@lucide/vue'
import BaseBadge from '../BaseBadge.vue'

// The hero's product picture: a student dashboard modelled on pages/Student/Dashboard
// (stat cards, today's classes, assignments due) plus two notices the platform sends.
// Demo data, labelled as such — it is no real student's record. Decorative for
// assistive technology; the figcaption carries the meaning.
const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

const stats = [
  { label: 'GPA', value: '3.42', detail: 'Cumulative' },
  { label: 'Attendance', value: '94%', detail: 'This semester', bar: 94 },
  { label: 'Credits', value: '18', detail: '6 courses' },
]

const classes = [
  { time: '08:00', code: 'CS201', name: 'Data Structures', room: 'Room B-204', state: 'Attended', variant: 'success' },
  { time: '10:30', code: 'CS210', name: 'Database Systems', room: 'Lab 3', state: 'Next', variant: 'primary' },
  { time: '14:00', code: 'MA202', name: 'Linear Algebra', room: 'Room A-105', state: 'Later', variant: 'muted' },
]

const due = [
  { title: 'ER diagram', meta: 'CS210 · due Friday', state: 'To submit', variant: 'warning' },
  { title: 'Sorting benchmark', meta: 'CS201 · submitted', state: 'Submitted', variant: 'success' },
]
</script>

<template>
  <figure class="mx-auto w-full max-w-2xl">
    <div class="relative lg:pb-16 lg:pl-12">
      <div class="overflow-hidden rounded-xl border border-border-default bg-surface shadow-lg dark:border-dark-border dark:bg-dark-surface" aria-hidden="true">
        <div class="flex items-center gap-2 border-b border-border-default bg-background px-4 py-3 dark:border-dark-border dark:bg-dark-bg">
          <span class="h-3 w-3 rounded-pill bg-border-muted dark:bg-dark-border" />
          <span class="h-3 w-3 rounded-pill bg-border-muted dark:bg-dark-border" />
          <span class="h-3 w-3 rounded-pill bg-border-muted dark:bg-dark-border" />
          <span class="ml-2 flex min-w-0 items-center gap-2 text-caption font-semibold text-ink dark:text-dark-ink">
            <img :src="symbolMark" alt="" class="h-4 w-4 rounded-sm" />
            <span class="truncate">EduCore · Student dashboard</span>
          </span>
          <BaseBadge variant="warning" class="ml-auto shrink-0">Demo data</BaseBadge>
        </div>

        <div class="space-y-4 p-4 sm:p-5">
          <div>
            <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Semester 1 · 2026–2027</p>
            <p class="font-display mt-1 text-h4 text-primary-dark dark:text-dark-ink">Your academic overview</p>
          </div>

          <div class="grid grid-cols-3 gap-2 sm:gap-3">
            <div v-for="s in stats" :key="s.label" class="min-w-0 rounded-lg border border-border-default p-3 dark:border-dark-border">
              <p class="truncate text-caption text-muted dark:text-dark-muted">{{ s.label }}</p>
              <p class="mt-1 text-h4 font-bold tabular-nums text-ink sm:text-h3 dark:text-dark-ink">{{ s.value }}</p>
              <div v-if="s.bar" class="mt-1 h-1 overflow-hidden rounded-pill bg-border-default dark:bg-dark-border">
                <div class="h-full rounded-pill bg-chart-primary dark:bg-dark-chart-primary" :style="{ width: `${s.bar}%` }" />
              </div>
              <p v-else class="truncate text-caption text-muted dark:text-dark-muted">{{ s.detail }}</p>
            </div>
          </div>

          <div class="grid gap-3 sm:grid-cols-[3fr_2fr]">
            <div class="rounded-lg border border-border-default p-3 dark:border-dark-border">
              <p class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink">
                <CalendarClock class="h-4 w-4 text-primary dark:text-dark-primary" /> Today's classes
              </p>
              <ul class="mt-2 divide-y divide-border-default dark:divide-dark-border">
                <li v-for="c in classes" :key="c.code" class="flex items-center gap-3 py-2">
                  <span class="w-12 shrink-0 text-caption font-semibold tabular-nums text-muted dark:text-dark-muted">{{ c.time }}</span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-small font-medium text-ink dark:text-dark-ink">{{ c.code }} {{ c.name }}</span>
                    <span class="block truncate text-caption text-muted dark:text-dark-muted">{{ c.room }}</span>
                  </span>
                  <BaseBadge :variant="c.variant" class="shrink-0">{{ c.state }}</BaseBadge>
                </li>
              </ul>
            </div>

            <div class="hidden rounded-lg border border-border-default p-3 sm:block dark:border-dark-border">
              <p class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink">
                <ClipboardList class="h-4 w-4 text-primary dark:text-dark-primary" /> Assignments due
              </p>
              <ul class="mt-2 space-y-3">
                <li v-for="d in due" :key="d.title" class="min-w-0">
                  <p class="truncate text-small font-medium text-ink dark:text-dark-ink">{{ d.title }}</p>
                  <p class="truncate text-caption text-muted dark:text-dark-muted">{{ d.meta }}</p>
                  <BaseBadge :variant="d.variant" class="mt-1">{{ d.state }}</BaseBadge>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Notices: below the window on phones, overlapping its corners from lg. -->
      <div
        class="edu-pop relative z-10 mx-4 -mt-6 flex items-start gap-3 rounded-lg border border-border-default bg-surface p-3 shadow-md sm:mx-8 lg:absolute lg:bottom-0 lg:left-0 lg:mx-0 lg:mt-0 lg:w-72 dark:border-dark-border dark:bg-dark-surface"
        style="animation-delay: 1.3s"
        aria-hidden="true"
      >
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
          <Send class="h-4 w-4" />
        </span>
        <span class="min-w-0">
          <span class="block text-small font-semibold text-ink dark:text-dark-ink">Grade published</span>
          <span class="block text-caption text-muted dark:text-dark-muted">CS201 Data Structures — open EduCore to see your result</span>
          <span class="mt-1 flex items-center gap-1 text-caption text-muted dark:text-dark-muted"><BellRing class="h-3 w-3" /> Inbox · Email · Telegram</span>
        </span>
      </div>

      <div
        class="edu-pop absolute -top-6 -right-2 z-10 hidden items-center gap-3 rounded-lg border border-border-default bg-surface p-3 shadow-md lg:flex dark:border-dark-border dark:bg-dark-surface"
        style="animation-delay: 1.05s"
        aria-hidden="true"
      >
        <CircleCheck class="h-5 w-5 shrink-0 text-success" />
        <span>
          <span class="block text-small font-semibold text-ink dark:text-dark-ink">Registration confirmed</span>
          <span class="block text-caption text-muted dark:text-dark-muted">6 sections · 18 credits · no clashes</span>
        </span>
      </div>
    </div>

    <figcaption class="mt-4 text-center text-caption text-muted lg:pl-12 dark:text-dark-muted">
      Illustrative student dashboard with demo data — not a real student's record.
    </figcaption>
  </figure>
</template>
