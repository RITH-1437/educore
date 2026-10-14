<script setup>
import { Database, KeyRound, Network, Workflow } from '@lucide/vue'
import PlatformRoles from './PlatformRoles.vue'
import Reveal from './Reveal.vue'

const principles = [
  { icon: Database, title: 'Centralized information', text: 'Departments, programs, courses, students and staff live in one database every module reads from.' },
  { icon: Network, title: 'Connected academic workflows', text: 'Registration, attendance, coursework, exams and grades build on each other instead of being re-typed.' },
  { icon: KeyRound, title: 'Role-based access', text: 'Five roles, each with its own workspace; the server checks every page and request against the role.' },
  { icon: Workflow, title: 'Digital administration', text: 'Document requests, invoices, internships and announcements move online, with a record of who did what.' },
]
</script>

<template>
  <section id="platform" class="scroll-mt-20 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="platform-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid gap-12 lg:grid-cols-[2fr_3fr] lg:gap-16">
        <Reveal>
          <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">The platform</p>
          <h2 id="platform-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
            A Connected Digital Foundation for Universities
          </h2>
          <p class="mt-4 text-body text-muted dark:text-dark-muted">
            EduCore brings a university's academic and administrative work onto one web platform. Structure, people,
            courses, results and paperwork share the same records, so nothing has to be copied from one office to the
            next.
          </p>
        </Reveal>

        <ul class="grid gap-x-10 gap-y-8 sm:grid-cols-2">
          <Reveal v-for="(p, i) in principles" :key="p.title" as="li" :delay="(i % 2) * 80" class="flex gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
              <component :is="p.icon" class="h-5 w-5" aria-hidden="true" />
            </span>
            <div>
              <h3 class="text-body font-semibold text-primary-dark dark:text-dark-ink">{{ p.title }}</h3>
              <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ p.text }}</p>
            </div>
          </Reveal>
        </ul>
      </div>

      <PlatformRoles class="mt-16 lg:mt-20" />
    </div>
  </section>
</template>
