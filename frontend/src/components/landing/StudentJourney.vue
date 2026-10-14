<script setup>
import {
  Award,
  BookMarked,
  Briefcase,
  CalendarClock,
  ClipboardList,
  FileCheck,
  FileText,
  ListChecks,
  Presentation,
  UserCheck,
  UserRound,
  GraduationCap,
} from '@lucide/vue'
import { computed, ref } from 'vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'
import { useInView } from './useInView'

// The stages a student record moves through, each one an implemented module
// (docs/3 §9). Phases group them; `span` is the phase's width on the desktop timeline.
const phases = [
  {
    name: 'Admission',
    steps: [
      { icon: UserRound, label: 'Student profile', text: 'Student number, program and status in one record.' },
      { icon: BookMarked, label: 'Program', text: 'A degree program and its curriculum.' },
    ],
  },
  {
    name: 'Every semester',
    steps: [
      { icon: ListChecks, label: 'Course registration', text: 'Prerequisites, credit limit and seats checked.' },
      { icon: CalendarClock, label: 'Timetable', text: 'A weekly schedule with rooms and lecturers.' },
      { icon: UserCheck, label: 'Attendance', text: 'Present, late, absent or excused per class.' },
      { icon: ClipboardList, label: 'Assignments', text: 'Submitted online, graded with feedback.' },
      { icon: FileCheck, label: 'Exams', text: 'Scheduled exams and released results.' },
    ],
  },
  {
    name: 'Results',
    steps: [{ icon: Award, label: 'Grades & GPA', text: 'Approved grades update semester and cumulative GPA.' }],
  },
  {
    name: 'Beyond class',
    steps: [
      { icon: FileText, label: 'Documents', text: 'Transcripts and certificates requested online.' },
      { icon: Briefcase, label: 'Internship', text: 'Application, reports and evaluations.' },
    ],
  },
]

// Flat list with running numbers for the timeline itself.
const steps = computed(() => {
  let n = 0
  return phases.flatMap((phase) => phase.steps.map((step) => ({ ...step, n: ++n, phase: phase.name })))
})

const views = [
  { icon: GraduationCap, who: 'Students', text: 'see their timetable, attendance per course, assignments due, exam dates, grades and GPA on one dashboard, and request documents without visiting an office.' },
  { icon: Presentation, who: 'Lecturers and staff', text: 'work from the same enrollments: registers, grade sheets, approvals and document requests all point to the same student record.' },
]

const timeline = ref(null)
const visible = useInView(timeline, { threshold: 0.2 })
</script>

<template>
  <section id="journey" class="scroll-mt-20 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="journey-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="journey-title"
        eyebrow="How it works · Student journey"
        title="Connected Throughout the Student Journey"
        description="A student's record moves through every stage below, and each stage builds on the one before it — the same record that lecturers and staff work with."
      />

      <div ref="timeline" class="relative mt-14">
        <!-- Phase labels (desktop): each spans its own steps. -->
        <div class="mb-6 hidden grid-cols-10 gap-2 lg:grid" aria-hidden="true">
          <p
            v-for="phase in phases"
            :key="phase.name"
            class="border-t-2 border-primary/30 pt-2 text-center text-caption font-semibold tracking-widest text-muted uppercase dark:border-dark-primary/40 dark:text-dark-muted"
            :style="{ gridColumn: `span ${phase.steps.length} / span ${phase.steps.length}` }"
          >
            {{ phase.name }}
          </p>
        </div>

        <div class="relative">
          <!-- Track: vertical on phones and tablets, horizontal from lg (between the first and last node centres). -->
          <div
            class="absolute top-5 bottom-5 left-5 w-0.5 -translate-x-1/2 rounded-pill bg-border-default lg:right-[5%] lg:bottom-auto lg:left-[5%] lg:h-0.5 lg:w-auto lg:translate-x-0 dark:bg-dark-border"
            aria-hidden="true"
          >
            <div
              class="h-full w-full origin-top rounded-pill bg-primary transition-[scale] duration-1000 ease-out lg:origin-left dark:bg-dark-primary"
              :class="visible ? 'scale-100' : 'scale-y-0 lg:scale-x-0 lg:scale-y-100'"
            />
          </div>

          <ol class="relative grid gap-5 lg:grid-cols-10 lg:gap-2">
            <li
              v-for="(step, i) in steps"
              :key="step.label"
              class="flex items-start gap-4 transition-[opacity,translate] duration-500 ease-out lg:flex-col lg:items-center lg:gap-3 lg:text-center"
              :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'"
              :style="{ transitionDelay: `${i * 80}ms` }"
            >
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill border-2 border-primary bg-surface text-primary shadow-sm dark:border-dark-primary dark:bg-dark-surface dark:text-dark-primary">
                <component :is="step.icon" class="h-4 w-4" aria-hidden="true" />
              </span>
              <div class="pt-1 lg:pt-0">
                <p class="text-caption font-semibold text-muted tabular-nums dark:text-dark-muted">
                  {{ String(step.n).padStart(2, '0') }}<span class="lg:hidden"> · {{ step.phase }}</span>
                </p>
                <h3 class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ step.label }}</h3>
                <p class="mt-1 text-small text-muted lg:text-caption dark:text-dark-muted">{{ step.text }}</p>
              </div>
            </li>
          </ol>
        </div>
      </div>

      <div class="mt-14 grid gap-4 md:grid-cols-2">
        <Reveal v-for="(v, i) in views" :key="v.who" :delay="i * 80" class="flex gap-4 rounded-xl border border-border-default bg-background p-5 dark:border-dark-border dark:bg-dark-bg">
          <component :is="v.icon" class="mt-0.5 h-5 w-5 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
          <p class="text-small text-muted dark:text-dark-muted">
            <span class="font-semibold text-primary-dark dark:text-dark-ink">{{ v.who }}</span> {{ v.text }}
          </p>
        </Reveal>
      </div>
    </div>
  </section>
</template>
