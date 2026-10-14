<script setup>
import { Building2, GraduationCap, Landmark, Presentation, QrCode, ShieldCheck } from '@lucide/vue'
import { ref } from 'vue'
import Reveal from './Reveal.vue'
import { useInView } from './useInView'

// The five roles of the platform (docs/3 §9.1) plus the public verification
// page, around the records they share. Connectors grow in once, on first view.
const staff = [
  { icon: ShieldCheck, name: 'Super Admin', text: 'Accounts and roles, error logs and the audit trail.' },
  { icon: Landmark, name: 'University Admin', text: 'Structure, students, finance, documents and analytics for the whole university.' },
  { icon: Building2, name: 'Department Admin', text: "Their department's sections, schedules, requests and analytics." },
]
const learning = [
  { icon: Presentation, name: 'Lecturer', text: 'Timetable, attendance, coursework, exams and grade sheets for their sections.' },
  { icon: GraduationCap, name: 'Student', text: 'Registration, timetable, grades and GPA, documents, invoices and internships.' },
  { icon: QrCode, name: 'Anyone with a document', text: 'Checks an issued PDF on the public verification page — no account needed.' },
]
const records = ['Structure', 'People', 'Teaching', 'Results', 'Documents', 'Finance']

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'
const hub = ref(null)
const visible = useInView(hub, { threshold: 0.3 })
</script>

<template>
  <div>
    <Reveal class="mx-auto max-w-2xl text-center">
      <h3 class="font-display text-h3 text-primary-dark dark:text-dark-ink">Who works in EduCore</h3>
      <p class="mt-2 text-small text-muted dark:text-dark-muted">
        Five roles sign in to their own workspace on the same records. Every role sees only what it is allowed to.
      </p>
    </Reveal>

    <div ref="hub" class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto_1fr] lg:gap-x-8 lg:gap-y-0">
      <ul class="space-y-4" aria-label="Administration roles">
        <Reveal v-for="(role, i) in staff" :key="role.name" as="li" from="left" :delay="i * 80" class="relative">
          <div class="flex gap-3 rounded-lg border border-border-default bg-background p-4 dark:border-dark-border dark:bg-dark-bg">
            <component :is="role.icon" class="mt-0.5 h-5 w-5 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
            <div>
              <p class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ role.name }}</p>
              <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ role.text }}</p>
            </div>
          </div>
          <span
            class="absolute top-1/2 left-full hidden h-px w-8 origin-left bg-border-muted transition-transform duration-500 ease-out lg:block dark:bg-dark-border"
            :class="visible ? 'scale-x-100' : 'scale-x-0'"
            :style="{ transitionDelay: `${300 + i * 80}ms` }"
            aria-hidden="true"
          />
        </Reveal>
      </ul>

      <Reveal
        from="scale"
        class="order-first flex flex-col items-center justify-center rounded-xl bg-primary-dark p-6 text-center sm:col-span-2 lg:order-none lg:col-span-1 lg:w-64 dark:border dark:border-dark-border"
      >
        <img :src="symbolMark" alt="" width="48" height="48" class="h-12 w-12 rounded-lg object-cover" />
        <p class="font-display mt-3 text-h4 text-dark-ink">EduCore</p>
        <p class="mt-1 text-small text-dark-muted">One shared set of academic records</p>
        <ul class="mt-4 flex flex-wrap justify-center gap-2" aria-label="What the records cover">
          <li v-for="r in records" :key="r" class="rounded-pill bg-dark-surface-2 px-3 py-1 text-caption font-medium text-dark-ink">{{ r }}</li>
        </ul>
      </Reveal>

      <ul class="space-y-4" aria-label="Teaching, learning and public roles">
        <Reveal v-for="(role, i) in learning" :key="role.name" as="li" from="right" :delay="i * 80" class="relative">
          <span
            class="absolute top-1/2 right-full hidden h-px w-8 origin-right bg-border-muted transition-transform duration-500 ease-out lg:block dark:bg-dark-border"
            :class="visible ? 'scale-x-100' : 'scale-x-0'"
            :style="{ transitionDelay: `${300 + i * 80}ms` }"
            aria-hidden="true"
          />
          <div class="flex gap-3 rounded-lg border border-border-default bg-background p-4 dark:border-dark-border dark:bg-dark-bg">
            <component :is="role.icon" class="mt-0.5 h-5 w-5 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
            <div>
              <p class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ role.name }}</p>
              <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ role.text }}</p>
            </div>
          </div>
        </Reveal>
      </ul>
    </div>
  </div>
</template>
