<script setup>
import {
  Award,
  BellRing,
  BookMarked,
  Building2,
  Calculator,
  Calendar,
  ClipboardList,
  FileCheck,
  FileText,
  FolderTree,
  GraduationCap,
  Landmark,
  Library,
  ListChecks,
  Mail,
  Megaphone,
  Receipt,
  School,
  Send,
  TableProperties,
  UserCheck,
  UserRound,
} from '@lucide/vue'
import { computed, ref } from 'vue'
import LandingTabs from './LandingTabs.vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const categories = [
  {
    key: 'academic',
    label: 'Academic',
    icon: Landmark,
    summary: 'The academic structure every other module builds on.',
    roles: ['University Admin', 'Faculty Admin'],
    modules: [
      { icon: Library, title: 'Courses', text: 'Course catalogue with credits and prerequisites.' },
      { icon: BookMarked, title: 'Programs', text: 'Degree programs and the courses in each curriculum.' },
      { icon: Calendar, title: 'Semesters', text: 'Academic years and semesters that frame every record.' },
      { icon: TableProperties, title: 'Classes', text: 'Offerings and sections with rooms, capacity and lecturers.' },
      { icon: ListChecks, title: 'Enrollment', text: 'Registration that checks prerequisites, capacity and duplicates.' },
    ],
  },
  {
    key: 'student',
    label: 'Student',
    icon: GraduationCap,
    summary: 'Everything a student and their lecturers work with during a semester.',
    roles: ['Student', 'Lecturer'],
    modules: [
      { icon: UserRound, title: 'Profile', text: 'Student record, program and academic status.' },
      { icon: UserCheck, title: 'Attendance', text: 'Registers per class session and a rate per course.' },
      { icon: ClipboardList, title: 'Assignments', text: 'Published tasks, submissions and grading.' },
      { icon: FileCheck, title: 'Exams', text: 'Scheduled exams and published results.' },
      { icon: Award, title: 'Grades', text: 'Grade sheets submitted, approved and finalized.' },
      { icon: Calculator, title: 'GPA', text: 'Semester and cumulative GPA, weighted by credits.' },
    ],
  },
  {
    key: 'administration',
    label: 'Administration',
    icon: Building2,
    summary: 'The offices that keep the university running, each with its own scope.',
    roles: ['Super Admin', 'University Admin', 'Faculty Admin'],
    modules: [
      { icon: School, title: 'Faculties', text: 'Faculties managed together with their departments.' },
      { icon: FolderTree, title: 'Departments', text: 'Departments that own programs and lecturers.' },
      { icon: FileText, title: 'Documents', text: 'Requests, approval, PDF generation and verification.' },
      { icon: Receipt, title: 'Invoices', text: 'Invoices, payments and receipts for each student.' },
      { icon: Megaphone, title: 'Announcements', text: 'Targeted news with file attachments.' },
    ],
  },
  {
    key: 'communication',
    label: 'Communication',
    icon: Send,
    summary: 'Information reaches people where they already are, on the channels they choose.',
    roles: ['Every role'],
    modules: [
      { icon: BellRing, title: 'Notifications', text: 'Registration, grades, documents, invoices and announcements notify the people concerned.' },
      { icon: Mail, title: 'Email', text: 'Every notification by email; document and payment updates always.' },
      { icon: Send, title: 'Telegram', text: 'The same messages in Telegram for users who link a chat.' },
    ],
  },
]

const active = ref(categories[0].key)
const current = computed(() => categories.find((c) => c.key === active.value))
</script>

<template>
  <section id="modules" class="scroll-mt-24 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="modules-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="modules-title"
        eyebrow="Platform modules"
        title="Grouped the way a university works"
        description="EduCore's modules fall into four areas. Pick one to see what it covers and who uses it."
      />

      <Reveal class="mt-10">
        <LandingTabs v-model="active" :tabs="categories" id-prefix="modules" label="Module categories" />
      </Reveal>

      <div class="mt-10">
        <Transition name="edu-panel" mode="out-in">
          <div
            :id="`modules-panel-${current.key}`"
            :key="current.key"
            role="tabpanel"
            :aria-labelledby="`modules-tab-${current.key}`"
            tabindex="0"
            class="grid gap-8 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary lg:grid-cols-[1fr_2fr]"
          >
            <div class="rounded-xl bg-primary-dark p-6 text-dark-ink dark:border dark:border-dark-border">
              <component :is="current.icon" class="h-6 w-6 text-secondary" aria-hidden="true" />
              <h3 class="font-display mt-4 text-h3">{{ current.label }}</h3>
              <p class="mt-2 text-small text-dark-muted">{{ current.summary }}</p>
              <p class="mt-6 text-caption font-semibold tracking-widest text-dark-muted uppercase">Used by</p>
              <ul class="mt-2 flex flex-wrap gap-2">
                <li v-for="role in current.roles" :key="role" class="rounded-pill bg-dark-surface-2 px-3 py-1 text-caption font-medium text-dark-ink">
                  {{ role }}
                </li>
              </ul>
              <p class="mt-6 text-caption text-dark-muted">{{ current.modules.length }} modules</p>
            </div>

            <ul class="grid content-start gap-x-8 sm:grid-cols-2">
              <li v-for="m in current.modules" :key="m.title" class="flex items-start gap-3 border-t border-border-default py-4 dark:border-dark-border">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 dark:bg-dark-primary/15">
                  <component :is="m.icon" class="h-4 w-4 text-primary dark:text-dark-primary" aria-hidden="true" />
                </span>
                <div>
                  <h4 class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ m.title }}</h4>
                  <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ m.text }}</p>
                </div>
              </li>
            </ul>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>
