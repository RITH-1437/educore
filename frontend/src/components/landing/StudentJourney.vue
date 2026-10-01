<script setup>
import {
  BookOpen,
  Building2,
  CalendarCheck,
  CalendarDays,
  ClipboardCheck,
  ClipboardList,
  FileText,
  FolderOpen,
  GraduationCap,
  TrendingUp,
  UserRound,
} from '@lucide/vue'
import Reveal from './Reveal.vue'

const steps = [
  { icon: UserRound, label: 'Admission & Profile' },
  { icon: BookOpen, label: 'Program' },
  { icon: ClipboardCheck, label: 'Course Registration' },
  { icon: CalendarDays, label: 'Class Schedule' },
  { icon: CalendarCheck, label: 'Attendance' },
  { icon: ClipboardList, label: 'Assignments' },
  { icon: FileText, label: 'Exams' },
  { icon: GraduationCap, label: 'Grades' },
  { icon: TrendingUp, label: 'GPA' },
  { icon: FolderOpen, label: 'Documents' },
  { icon: Building2, label: 'Internship' },
]
</script>

<template>
  <section id="journey" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid items-end gap-10 lg:grid-cols-[0.8fr_1.2fr]">
        <Reveal class="lg:max-w-md">
          <p class="text-xs font-semibold tracking-[0.2em] text-blue-600 uppercase">Student Experience</p>
          <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Designed Around the Student Journey
          </h2>
          <p class="mt-4 text-lg leading-relaxed text-slate-600">
            EduCore connects the major stages of a student's academic journey, giving students and
            university staff a clearer view of academic progress.
          </p>
        </Reveal>

        <ol class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4">
          <Reveal
            as="li"
            v-for="(s, i) in steps"
            :key="s.label"
            :delay="i * 60"
            class="group rounded-xl border border-slate-200 bg-white p-4 transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-md"
          >
            <div class="flex items-center justify-between">
              <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 transition duration-300 group-hover:bg-blue-600">
                <component :is="s.icon" class="h-5 w-5 text-slate-500 transition duration-300 group-hover:text-white" />
              </span>
              <span class="font-display text-xs font-bold text-slate-400" aria-hidden="true">
                {{ String(i + 1).padStart(2, '0') }}
              </span>
            </div>
            <p class="mt-3 text-sm font-semibold text-slate-800">{{ s.label }}</p>
          </Reveal>
        </ol>
      </div>
    </div>
  </section>
</template>