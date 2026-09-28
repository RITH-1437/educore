<script setup>
import {
  Activity,
  Bell,
  BookOpen,
  CalendarCheck,
  CalendarDays,
  ClipboardCheck,
  FileText,
  FolderOpen,
  GraduationCap,
  Landmark,
  Mail,
  MessageSquare,
  Send,
  Users,
} from '@lucide/vue'
import { ref } from 'vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const categories = [
  {
    key: 'academic',
    label: 'Academic',
    icon: Landmark,
    modules: [
      { icon: Landmark, title: 'Programs & Curricula', text: 'Structure programs, curricula, and degree requirements.' },
      { icon: CalendarDays, title: 'Academic Years & Semesters', text: 'Define the academic calendar that drives the platform.' },
      { icon: BookOpen, title: 'Courses & Sections', text: 'Offer courses, prerequisites, and class sections.' },
      { icon: FileText, title: 'Exams & Grades', text: 'Record results and compute academic standing.' },
      { icon: GraduationCap, title: 'Grades & GPA', text: 'Track academic progress across the entire lifecycle.' },
    ],
  },
  {
    key: 'students',
    label: 'Student Services',
    icon: Users,
    modules: [
      { icon: Users, title: 'Student Profiles', text: 'Central records from admission through graduation.' },
      { icon: ClipboardCheck, title: 'Enrollment', text: 'Course and program enrollment with history.' },
      { icon: CalendarCheck, title: 'Attendance', text: 'Session attendance visible to staff and students.' },
      { icon: FolderOpen, title: 'Document Requests', text: 'Request transcripts and institutional documents digitally.' },
    ],
  },
  {
    key: 'admin',
    label: 'Administration',
    icon: ClipboardCheck,
    modules: [
      { icon: Landmark, title: 'Faculties & Departments', text: "Model the institution's organizational structure." },
      { icon: Users, title: 'Lecturer Management', text: 'Teaching assignments and workload overview.' },
      { icon: CalendarDays, title: 'Timetable & Scheduling', text: 'Build schedules across rooms, sections, and staff.' },
      { icon: Bell, title: 'Announcements', text: 'Broadcast updates to the right audience.' },
    ],
  },
  {
    key: 'communication',
    label: 'Communication',
    icon: MessageSquare,
    modules: [
      { icon: Bell, title: 'In-Platform Notifications', text: 'Priority updates inside the EduCore workspace.' },
      { icon: Mail, title: 'Email', text: 'Transactional and scheduled email delivery.' },
      { icon: Send, title: 'Telegram', text: 'Notifications delivered where people already are.' },
    ],
  },
  {
    key: 'documents',
    label: 'Documents',
    icon: FolderOpen,
    modules: [
      { icon: FolderOpen, title: 'Digital Records', text: 'Store and organize institutional documents.' },
      { icon: ClipboardCheck, title: 'Document Verification', text: 'Verification workflows for official records.' },
      { icon: GraduationCap, title: 'QR Verification', text: 'Machine-readable verification for issued documents.' },
    ],
  },
  {
    key: 'analytics',
    label: 'Analytics',
    icon: Activity,
    modules: [
      { icon: Activity, title: 'Enrollment Insights', text: 'Understand intake and program demand.' },
      { icon: GraduationCap, title: 'Academic Performance', text: 'Monitor grades, GPA, and progression.' },
      { icon: CalendarDays, title: 'Attendance Trends', text: 'Spot patterns across sessions and cohorts.' },
    ],
  },
]

const active = ref(categories[0].key)
const resolved = (key) => categories.find((c) => c.key === key)
const bars = [92, 78, 62, 84, 55, 71]
</script>

<template>
  <section id="modules" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        eyebrow="Core Modules"
        title="One Platform. Every Academic Workflow."
        description="Explore how EduCore organizes the workflows behind university operations — grouped the way a university actually works."
      />

      <Reveal class="mt-10">
        <div class="no-scrollbar flex gap-2 overflow-x-auto pb-2 sm:justify-center" role="tablist" aria-label="Module categories">
          <button
            v-for="cat in categories"
            :key="cat.key"
            type="button"
            role="tab"
            :aria-selected="active === cat.key"
            class="flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
            :class="active === cat.key ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'"
            @click="active = cat.key"
          >
            <component :is="cat.icon" class="h-4 w-4" />
            {{ cat.label }}
          </button>
        </div>
      </Reveal>

      <div class="mt-10">
        <Transition name="edu-panel" mode="out-in">
          <div :key="active" class="grid items-stretch gap-6 lg:grid-cols-[1fr_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
              <p class="text-xs font-semibold tracking-[0.2em] text-blue-600 uppercase">
                {{ resolved(active).label }}
              </p>
              <ul class="mt-5 space-y-3">
                <li
                  v-for="m in resolved(active).modules"
                  :key="m.title"
                  class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4"
                >
                  <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                    <component :is="m.icon" class="h-4.5 w-4.5 text-blue-600" />
                  </span>
                  <div>
                    <h4 class="font-display text-sm font-semibold text-slate-900">{{ m.title }}</h4>
                    <p class="mt-0.5 text-sm text-slate-500">{{ m.text }}</p>
                  </div>
                </li>
              </ul>
            </div>

            <div class="relative flex flex-col overflow-hidden rounded-2xl bg-slate-950 p-6 sm:p-8">
              <div class="bg-grid-dark absolute inset-0" aria-hidden="true" />
              <div class="absolute -top-16 right-0 h-48 w-48 rounded-full bg-blue-600/20 blur-3xl" aria-hidden="true" />
              <div class="relative">
                <p class="text-xs font-semibold tracking-[0.2em] text-sky-400 uppercase">Preview</p>
                <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/20">
                        <component :is="resolved(active).icon" class="h-4 w-4 text-sky-400" />
                      </span>
                      <p class="text-sm font-semibold text-white">{{ resolved(active).label }} overview</p>
                    </div>
                    <span class="rounded-full bg-teal-500/15 px-2 py-0.5 text-[11px] font-semibold text-teal-400">Live data</span>
                  </div>
                  <dl class="mt-5 space-y-4">
                    <div v-for="(b, i) in bars" :key="i" class="flex items-center gap-3">
                      <dt class="w-32 shrink-0 truncate text-xs text-slate-400">
                        {{ ['Records', 'Activity', 'Active users', 'Submissions', 'Requests', 'Reports'][i] }}
                      </dt>
                      <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/10">
                        <div
                          class="h-full rounded-full transition-all duration-500"
                          :class="i % 3 === 2 ? 'bg-teal-400' : i % 3 === 1 ? 'bg-sky-400' : 'bg-blue-500'"
                          :style="{ width: `${b}%` }"
                        />
                      </div>
                    </div>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>