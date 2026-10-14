<script setup>
import { BellRing, CalendarClock, Eye, Mail, Megaphone, Send } from '@lucide/vue'
import { ref } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import Reveal from './Reveal.vue'
import { useInView } from './useInView'

// Announcement audiences and delivery channels as implemented (reports 24, 25,
// 42, 48, 51). The fan-out on the right is a sample, captioned as such.
const audiences = ['Everyone', 'A role group', 'A department', 'A program', 'A section', 'A course']

const channels = [
  { icon: BellRing, title: 'In-app inbox', text: 'Every notice is kept in an inbox with an unread count on every page.' },
  { icon: Mail, title: 'Email', text: 'Queued and retried; invoice, payment and document updates are always sent.' },
  { icon: Send, title: 'Telegram', text: 'Opt-in after one-tap linking — plus a reminder about 30 minutes before each class.' },
]

const deliveries = [
  { icon: BellRing, channel: 'In-app', title: 'Midterm timetable published', meta: 'Inbox · unread' },
  { icon: Mail, channel: 'Email', title: 'Midterm timetable published', meta: 'To the student’s email' },
  { icon: Send, channel: 'Telegram', title: 'Midterm timetable published', meta: 'From the EduCore bot' },
]

const stage = ref(null)
const visible = useInView(stage, { threshold: 0.3 })
</script>

<template>
  <section id="communication" class="scroll-mt-20 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="communication-title">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[2fr_3fr] lg:gap-16 lg:px-8">
      <Reveal>
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Communication</p>
        <h2 id="communication-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          Keep University Communities Connected
        </h2>
        <p class="mt-4 text-body text-muted dark:text-dark-muted">
          Announcements follow the university's structure, and personal updates — a confirmed registration, a published
          grade, a ready document — go straight to the person concerned.
        </p>

        <p class="mt-6 text-caption font-semibold tracking-widest text-muted uppercase dark:text-dark-muted">Announce to</p>
        <ul class="mt-2 flex flex-wrap gap-2">
          <li v-for="a in audiences" :key="a" class="rounded-pill border border-border-default bg-surface px-3 py-1 text-caption font-medium text-ink dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink">
            {{ a }}
          </li>
        </ul>

        <ul class="mt-8 space-y-4">
          <li v-for="c in channels" :key="c.title" class="flex gap-3">
            <component :is="c.icon" class="mt-0.5 h-5 w-5 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
            <p class="text-small text-muted dark:text-dark-muted">
              <span class="font-semibold text-primary-dark dark:text-dark-ink">{{ c.title }}.</span> {{ c.text }}
            </p>
          </li>
        </ul>
      </Reveal>

      <figure ref="stage" class="min-w-0 self-center">
        <div aria-hidden="true">
          <div
            class="mx-auto max-w-md rounded-xl border border-border-default bg-surface p-5 shadow-md transition-[opacity,translate] duration-500 ease-out dark:border-dark-border dark:bg-dark-surface"
            :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
          >
            <div class="flex items-center gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary text-white dark:bg-dark-primary dark:text-dark-bg">
                <Megaphone class="h-5 w-5" />
              </span>
              <div class="min-w-0">
                <p class="truncate text-small font-semibold text-ink dark:text-dark-ink">Midterm timetable published</p>
                <p class="truncate text-caption text-muted dark:text-dark-muted">Announcement by a Department Admin</p>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-2">
              <BaseBadge variant="primary">To: Department of Computer Science</BaseBadge>
              <BaseBadge variant="muted"><CalendarClock class="h-3 w-3" /> Scheduled · Mon 08:00</BaseBadge>
              <BaseBadge variant="muted"><Eye class="h-3 w-3" /> Read by 214 of 260</BaseBadge>
            </div>
          </div>

          <!-- Fan-out connectors (from sm): one stem, a bar across the three channels, a stem into each. -->
          <div class="hidden sm:block">
            <div class="mx-auto h-6 w-px bg-border-muted transition-opacity duration-500 dark:bg-dark-border" :class="visible ? 'opacity-100' : 'opacity-0'" :style="{ transitionDelay: '300ms' }" />
            <div class="mx-auto h-px w-2/3 bg-border-muted transition-opacity duration-500 dark:bg-dark-border" :class="visible ? 'opacity-100' : 'opacity-0'" :style="{ transitionDelay: '400ms' }" />
          </div>

          <ul class="mt-4 grid gap-3 sm:mt-0 sm:grid-cols-3">
            <li
              v-for="(d, i) in deliveries"
              :key="d.channel"
              class="transition-[opacity,translate] duration-500 ease-out"
              :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
              :style="{ transitionDelay: `${600 + i * 250}ms` }"
            >
              <div class="mx-auto hidden h-6 w-px bg-border-muted sm:block dark:bg-dark-border" />
              <div class="rounded-lg border border-border-default bg-surface p-4 shadow-sm dark:border-dark-border dark:bg-dark-surface">
                <p class="flex items-center gap-2 text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">
                  <component :is="d.icon" class="h-4 w-4" /> {{ d.channel }}
                </p>
                <p class="mt-2 text-small font-semibold text-ink dark:text-dark-ink">{{ d.title }}</p>
                <p class="text-caption text-muted dark:text-dark-muted">{{ d.meta }}</p>
              </div>
            </li>
          </ul>
        </div>
        <figcaption class="mt-4 text-center text-caption text-muted dark:text-dark-muted">
          Sample announcement. Email can be limited to essential messages; Telegram is used only by people who link it.
        </figcaption>
      </figure>
    </div>
  </section>
</template>
