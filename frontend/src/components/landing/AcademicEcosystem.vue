<script setup>
import {
  BookMarked,
  BookOpen,
  Building2,
  CalendarCheck,
  CalendarDays,
  FolderOpen,
  GraduationCap,
  Landmark,
  Users,
} from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const levels = [
  { icon: Landmark, name: 'University', tag: 'Institution level' },
  { icon: Building2, name: 'Faculty', tag: 'Academic unit' },
  { icon: FolderOpen, name: 'Department', tag: 'Discipline groups' },
  { icon: BookOpen, name: 'Program', tag: 'Degree programs' },
  { icon: CalendarDays, name: 'Academic Year', tag: 'Annual cycle' },
  { icon: CalendarCheck, name: 'Semester', tag: 'Teaching terms' },
  { icon: BookMarked, name: 'Course', tag: 'Subjects & prerequisites' },
  { icon: Users, name: 'Class / Section', tag: 'Teaching groups' },
  { icon: GraduationCap, name: 'Student', tag: 'Individual academic activity' },
]
</script>

<template>
  <section id="structure" class="scroll-mt-24 bg-slate-50 py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        eyebrow="Academic Ecosystem"
        title="Designed Around the Academic Structure"
        description="EduCore follows the real organizational and academic structure of a university, allowing information to remain connected from institution level down to individual student activity."
      />

      <div class="relative mx-auto mt-16 max-w-3xl">
        <div class="absolute top-4 bottom-4 left-6 w-px bg-gradient-to-b from-blue-600 via-sky-500 to-teal-500 sm:left-7" aria-hidden="true" />

        <ol class="space-y-3">
          <li v-for="(lvl, i) in levels" :key="lvl.name">
            <Reveal
              :delay="i * 95"
              class="relative flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition duration-300 hover:border-blue-200 hover:shadow-md sm:gap-5 sm:p-5"
            >
              <span
                class="relative z-10 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-4 ring-slate-50"
                :class="i >= 6 ? 'bg-teal-500' : i >= 3 ? 'bg-sky-600' : 'bg-blue-600'"
              >
                <component :is="lvl.icon" class="h-5 w-5 text-white" />
              </span>
              <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                  <h3 class="font-display text-base font-semibold text-slate-900">{{ lvl.name }}</h3>
                  <p class="text-sm text-slate-500">{{ lvl.tag }}</p>
                </div>
                <span class="hidden text-xs font-semibold text-slate-300 sm:block">
                  L{{ i + 1 }}
                </span>
              </div>
            </Reveal>
          </li>
        </ol>

        <Reveal :delay="levels.length * 95" class="mt-8 text-center">
          <p class="text-sm text-slate-500">
            Every level stays connected — so a student record, a course, and a semester always share the same truth.
          </p>
        </Reveal>
      </div>
    </div>
  </section>
</template>