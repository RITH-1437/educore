<script setup>
import {
  Award,
  Briefcase,
  CalendarClock,
  Calculator,
  ClipboardList,
  FileCheck,
  FileText,
  BookMarked,
  ListChecks,
  UserCheck,
  UserRound,
} from '@lucide/vue'
import { ref } from 'vue'
import SectionHeading from './SectionHeading.vue'
import { useInView } from './useInView'

const steps = [
  { icon: UserRound, label: 'Student Profile', text: 'Student number, program and status.' },
  { icon: BookMarked, label: 'Program', text: 'Enrolled in a degree program.' },
  { icon: ListChecks, label: 'Course Registration', text: 'Sections chosen each semester, with checks.' },
  { icon: CalendarClock, label: 'Schedule', text: 'A weekly timetable of classes and rooms.' },
  { icon: UserCheck, label: 'Attendance', text: 'Present, late, absent or excused per session.' },
  { icon: ClipboardList, label: 'Assignments', text: 'Submitted online and graded by lecturers.' },
  { icon: FileCheck, label: 'Exams', text: 'Scheduled exams and published results.' },
  { icon: Award, label: 'Grades', text: 'Course grades once they are approved.' },
  { icon: Calculator, label: 'GPA', text: 'Semester and cumulative GPA.' },
  { icon: FileText, label: 'Documents', text: 'Transcripts and certificates on request.' },
  { icon: Briefcase, label: 'Internship', text: 'Applications reviewed and approved.' },
]

const timeline = ref(null)
const visible = useInView(timeline, { threshold: 0.25 })
</script>

<template>
  <section id="journey" class="scroll-mt-24 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="journey-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="journey-title"
        eyebrow="Student journey"
        title="One record, from first day to internship"
        description="Every stage of a student's life at the university happens in EduCore and builds on the stage before it."
      />

      <div ref="timeline" class="relative mt-14">
        <!-- Track: vertical on mobile, horizontal from lg (between the first and last node centres). -->
        <div
          class="absolute top-5 bottom-5 left-5 w-0.5 -translate-x-1/2 rounded-pill bg-border-default lg:right-[calc(100%/22)] lg:bottom-auto lg:left-[calc(100%/22)] lg:h-0.5 lg:w-auto lg:translate-x-0 dark:bg-dark-border"
          aria-hidden="true"
        >
          <div
            class="h-full w-full origin-top rounded-pill bg-primary transition-[scale] duration-1000 ease-out lg:origin-left dark:bg-dark-primary"
            :class="visible ? 'scale-100' : 'scale-y-0 lg:scale-x-0 lg:scale-y-100'"
          />
        </div>

        <ol class="relative grid gap-6 lg:grid-cols-11 lg:gap-2">
          <li
            v-for="(step, i) in steps"
            :key="step.label"
            class="flex items-start gap-4 transition-[opacity,translate] duration-500 ease-out lg:flex-col lg:items-center lg:gap-3 lg:text-center"
            :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'"
            :style="{ transitionDelay: `${i * 90}ms` }"
          >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill border-2 border-primary bg-surface text-primary shadow-sm dark:bg-dark-surface dark:text-dark-primary dark:border-dark-primary">
              <component :is="step.icon" class="h-4 w-4" aria-hidden="true" />
            </span>
            <div class="pt-2 lg:pt-0">
              <p class="text-caption font-semibold text-muted tabular-nums dark:text-dark-muted" aria-hidden="true">{{ String(i + 1).padStart(2, '0') }}</p>
              <h3 class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ step.label }}</h3>
              <p class="mt-1 text-small text-muted lg:sr-only dark:text-dark-muted">{{ step.text }}</p>
            </div>
          </li>
        </ol>
      </div>
    </div>
  </section>
</template>
