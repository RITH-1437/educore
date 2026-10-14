<script setup>
import {
  ArrowDown,
  ArrowRight,
  BellRing,
  Database,
  FileSpreadsheet,
  FileText,
  Files,
  MessagesSquare,
  Network,
  Repeat,
  Unlink,
  Workflow,
} from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

// Problems EduCore is designed to address, each paired with how the platform
// organises that work (only what the implemented modules actually do).
const pairs = [
  {
    problem: { icon: FileSpreadsheet, title: 'Scattered information', text: 'Student, course and staff records kept in separate spreadsheets and offices.' },
    solution: { icon: Database, title: 'One shared record', text: 'Departments, programs, courses, students and lecturers live in one database that every module reads from.' },
  },
  {
    problem: { icon: Repeat, title: 'Repetitive administrative work', text: 'The same details typed again for registration, grading and every certificate.' },
    solution: { icon: Workflow, title: 'Workflows that reuse the record', text: 'Registration checks prerequisites and seats; grade sheets compute from attendance, coursework and exams.' },
  },
  {
    problem: { icon: Unlink, title: 'Disconnected academic records', text: 'Enrollment, attendance and grades that do not link to each other.' },
    solution: { icon: Network, title: 'Connected academic data', text: 'Each grade knows its section, course, semester and program, so GPA and transcripts follow automatically.' },
  },
  {
    problem: { icon: Files, title: 'Paper-based document workflows', text: 'Queues at an office for transcripts and certificates, checked by hand.' },
    solution: { icon: FileText, title: 'Online document requests', text: 'Requested online, approved by staff, generated as PDFs and verifiable by QR code.' },
  },
  {
    problem: { icon: MessagesSquare, title: 'Communication across many channels', text: 'Notices spread over chat groups, notice boards and personal email.' },
    solution: { icon: BellRing, title: 'Targeted announcements', text: 'Reach a department, program, section or course — in the app inbox, by email and on Telegram.' },
  },
]
</script>

<template>
  <section id="solution" class="scroll-mt-20 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="solution-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="solution-title"
        eyebrow="Problem → Solution"
        title="From Fragmented Processes to One Connected Platform"
        description="Many universities still coordinate academic work through paper forms, spreadsheets and chat groups. These are the problems EduCore is designed to address — and how it organises each one."
      />

      <div class="mx-auto mt-12 max-w-5xl">
        <div class="hidden grid-cols-[1fr_3rem_1fr] gap-4 px-4 pb-3 text-caption font-semibold tracking-widest text-muted uppercase lg:grid dark:text-dark-muted" aria-hidden="true">
          <span>Common challenge</span>
          <span />
          <span class="text-primary dark:text-dark-primary">With EduCore</span>
        </div>

        <ol class="space-y-4">
          <Reveal
            v-for="(pair, i) in pairs"
            :key="pair.problem.title"
            as="li"
            :delay="i * 70"
            class="grid items-stretch gap-2 lg:grid-cols-[1fr_3rem_1fr] lg:gap-4"
          >
            <div class="flex gap-3 rounded-lg border border-dashed border-border-muted p-4 dark:border-dark-border">
              <component :is="pair.problem.icon" class="mt-0.5 h-5 w-5 shrink-0 text-muted dark:text-dark-muted" aria-hidden="true" />
              <div>
                <h3 class="text-small font-semibold text-ink dark:text-dark-ink">
                  <span class="sr-only">Challenge: </span>{{ pair.problem.title }}
                </h3>
                <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ pair.problem.text }}</p>
              </div>
            </div>

            <div class="flex items-center justify-center text-primary dark:text-dark-primary" aria-hidden="true">
              <ArrowRight class="hidden h-5 w-5 lg:block" />
              <ArrowDown class="h-4 w-4 lg:hidden" />
            </div>

            <div class="flex gap-3 rounded-lg border border-primary/20 bg-surface p-4 shadow-sm dark:border-dark-primary/30 dark:bg-dark-surface">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-white dark:bg-dark-primary dark:text-dark-bg">
                <component :is="pair.solution.icon" class="h-4 w-4" aria-hidden="true" />
              </span>
              <div>
                <p class="text-small font-semibold text-primary-dark dark:text-dark-ink">
                  <span class="sr-only">With EduCore: </span>{{ pair.solution.title }}
                </p>
                <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ pair.solution.text }}</p>
              </div>
            </div>
          </Reveal>
        </ol>
      </div>
    </div>
  </section>
</template>
