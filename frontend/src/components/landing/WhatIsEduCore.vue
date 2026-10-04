<script setup>
import { BarChart3, Building2, FileText, GraduationCap, Landmark, Megaphone } from '@lucide/vue'
import Reveal from './Reveal.vue'

const pillars = [
  { icon: Landmark, title: 'Academic management', text: 'Faculties, departments, programs, academic years, semesters, courses and class sections.' },
  { icon: GraduationCap, title: 'Student services', text: 'Course registration, timetable, attendance, assignments, exams, grades and GPA.' },
  { icon: Building2, title: 'Administration', text: 'Student and lecturer records, invoices, internships and approvals, each scoped to the right role.' },
  { icon: Megaphone, title: 'Communication', text: 'Announcements for the whole university, a faculty, program, section or course, plus email and Telegram.' },
  { icon: FileText, title: 'Documents', text: 'Transcripts and certificates requested online, issued as PDFs and verified by QR code.' },
  { icon: BarChart3, title: 'Analytics', text: 'Enrollment, attendance, academic performance and workload, exportable as CSV and PDF.' },
]
</script>

<template>
  <section id="platform" class="scroll-mt-24 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="platform-title">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[2fr_3fr] lg:gap-16 lg:px-8">
      <Reveal class="lg:sticky lg:top-32 lg:self-start">
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">What is EduCore?</p>
        <h2 id="platform-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          A centralized digital platform for modern university administration.
        </h2>
        <p class="mt-4 text-body text-muted dark:text-dark-muted">
          Instead of spreadsheets, paper forms and separate tools, every part of the university works from
          the same records. Administrators, faculty staff, lecturers and students each sign in to their
          own workspace and see what their role allows.
        </p>
      </Reveal>

      <ol class="grid gap-x-10 sm:grid-cols-2">
        <Reveal
          v-for="(pillar, i) in pillars"
          :key="pillar.title"
          as="li"
          :delay="(i % 2) * 80"
          class="border-t border-border-default py-6 dark:border-dark-border"
        >
          <div class="flex items-center gap-3">
            <span class="font-display text-small font-bold text-primary tabular-nums dark:text-dark-primary" aria-hidden="true">
              {{ String(i + 1).padStart(2, '0') }}
            </span>
            <component :is="pillar.icon" class="h-5 w-5 text-muted dark:text-dark-muted" aria-hidden="true" />
            <h3 class="font-display text-h4 text-primary-dark dark:text-dark-ink">{{ pillar.title }}</h3>
          </div>
          <p class="mt-2 text-small text-muted dark:text-dark-muted">{{ pillar.text }}</p>
        </Reveal>
      </ol>
    </div>
  </section>
</template>
