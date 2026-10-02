<script setup>
import {
  BarChart3,
  Bell,
  BookOpen,
  Building2,
  CalendarCheck,
  CalendarDays,
  CircleDollarSign,
  ClipboardCheck,
  ClipboardList,
  FileText,
  FolderOpen,
  GraduationCap,
  Landmark,
  ShieldCheck,
  UserCog,
  Users,
} from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const modules = [
  { icon: Landmark, title: 'Academic Management', text: 'Organize faculties, departments, programs, academic years, semesters, courses, and class sections.' },
  { icon: Users, title: 'Student Management', text: 'Maintain centralized student profiles, academic information, enrollment history, and student services.' },
  { icon: UserCog, title: 'Lecturer Management', text: 'Manage lecturer records, teaching assignments, and workload overview.' },
  { icon: BookOpen, title: 'Course Management', text: 'Manage courses, prerequisites, offerings, sections, and academic structures.' },
  { icon: ClipboardCheck, title: 'Enrollment', text: 'Track course and program enrollment with a clear academic history.' },
  { icon: CalendarDays, title: 'Timetable', text: 'Publish class schedules across sections, rooms, lecturers, and students.' },
  { icon: CalendarCheck, title: 'Attendance', text: 'Record and review session attendance for lecturers and students.' },
  { icon: ClipboardList, title: 'Assignments', text: 'Create assignments, collect submissions, and track grading.' },
  { icon: FileText, title: 'Exams', text: 'Handle exam sessions, results entry, and academic assessment records.' },
  { icon: GraduationCap, title: 'Grades & GPA', text: 'Manage assessments, grades, academic results, GPA calculations, and student academic progress.' },
  { icon: FolderOpen, title: 'Documents', text: 'Digitize document requests, records, verification, and institutional documents.' },
  { icon: CircleDollarSign, title: 'Finance Records', text: 'Keep invoices and payment records connected to students and programs.' },
  { icon: Bell, title: 'Notifications', text: 'Keep students and university staff informed through centralized notifications, email, and Telegram.' },
  { icon: Building2, title: 'Internship Management', text: 'Support internship placement, supervision, and project tracking.' },
  { icon: BarChart3, title: 'Analytics & Reporting', text: 'Turn enrollment, attendance, performance, and activity data into insight.' },
  { icon: ShieldCheck, title: 'Audit & Security', text: 'Enforce role-based access, audit logging, and controlled data access.' },
]
</script>

<template>
  <section id="platform" class="scroll-mt-24 bg-slate-50 py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        eyebrow="Platform Overview"
        title="Everything Your University Needs"
        description="EduCore provides a structured digital foundation for managing the complete academic lifecycle."
      />

      <div class="mt-14 grid gap-px overflow-hidden rounded-2xl bg-slate-200 ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <Reveal
          v-for="(m, i) in modules"
          :key="m.title"
          :delay="(i % 4) * 70"
          class="group bg-white p-6 transition duration-300 hover:bg-blue-50/40"
        >
          <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 transition duration-300 group-hover:bg-blue-600">
              <component :is="m.icon" class="h-5 w-5 text-slate-500 transition duration-300 group-hover:text-white" />
            </span>
            <div>
              <h3 class="font-display text-[15px] font-semibold text-slate-900 transition-colors duration-200 group-hover:text-blue-600">{{ m.title }}</h3>
              <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ m.text }}</p>
            </div>
          </div>
        </Reveal>
      </div>
    </div>
  </section>
</template>