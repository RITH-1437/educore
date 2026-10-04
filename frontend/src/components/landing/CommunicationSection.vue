<script setup>
import { Building2, FolderTree, GraduationCap, Landmark, Mail, Megaphone, Send, UserRound } from '@lucide/vue'
import { ref } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import Reveal from './Reveal.vue'
import { useInView } from './useInView'

const flow = [
  { icon: Landmark, label: 'University' },
  { icon: Building2, label: 'Faculty' },
  { icon: FolderTree, label: 'Department' },
  { icon: UserRound, label: 'Lecturer' },
  { icon: GraduationCap, label: 'Student' },
]

const channels = [
  { icon: Megaphone, title: 'In-app', text: 'Announcements on the feed and on every dashboard.' },
  { icon: Mail, title: 'Email', text: 'Every notification; document and payment updates always.' },
  { icon: Send, title: 'Telegram', text: 'The same messages for users who link a chat.' },
]

// Sample notifications, modelled on real EduCore events.
const notifications = [
  { icon: Megaphone, channel: 'In-app', title: 'Midterm timetable published', meta: 'Announcement · Faculty of Engineering' },
  { icon: Send, channel: 'Telegram', title: 'Grade published: CS305 Database Systems', meta: 'Open EduCore to see your result' },
  { icon: Mail, channel: 'Email', title: 'Your academic transcript is ready', meta: 'Document request · download the PDF' },
]

const stage = ref(null)
const visible = useInView(stage, { threshold: 0.3 })
</script>

<template>
  <section id="communication" class="scroll-mt-24 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="communication-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <Reveal class="max-w-3xl">
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Communication</p>
        <h2 id="communication-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          The right message reaches the right people
        </h2>
        <p class="mt-4 text-body text-muted dark:text-dark-muted">
          News follows the university's own structure: an announcement can go to everyone, one faculty,
          department, program, section or course. Personal updates go straight to the student concerned.
        </p>
      </Reveal>

      <div ref="stage" class="mt-12 grid grid-cols-1 gap-10 lg:grid-cols-[1fr_2fr] lg:gap-16">
        <div class="relative self-start">
          <div class="absolute top-5 bottom-5 left-5 w-0.5 -translate-x-1/2 rounded-pill bg-border-default dark:bg-dark-border" aria-hidden="true">
            <span v-if="visible" class="edu-travel absolute left-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-pill bg-primary shadow-sm dark:bg-dark-primary" />
          </div>
          <ol class="relative space-y-4" aria-label="How information flows">
            <li
              v-for="(step, i) in flow"
              :key="step.label"
              class="flex items-center gap-4 transition-[opacity,translate] duration-500 ease-out"
              :class="visible ? 'translate-x-0 opacity-100' : '-translate-x-2 opacity-0'"
              :style="{ transitionDelay: `${i * 100}ms` }"
            >
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill border border-border-default bg-surface text-primary shadow-sm dark:border-dark-border dark:bg-dark-surface dark:text-dark-primary">
                <component :is="step.icon" class="h-4 w-4" aria-hidden="true" />
              </span>
              <span class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ step.label }}</span>
            </li>
          </ol>
        </div>

        <div class="min-w-0 space-y-8">
          <ul class="grid gap-4 sm:grid-cols-3">
            <li v-for="c in channels" :key="c.title" class="border-t-2 border-primary pt-4 dark:border-dark-primary">
              <p class="flex items-center gap-2 text-small font-semibold text-primary-dark dark:text-dark-ink">
                <component :is="c.icon" class="h-4 w-4 text-primary dark:text-dark-primary" aria-hidden="true" />
                {{ c.title }}
              </p>
              <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ c.text }}</p>
            </li>
          </ul>

          <figure>
            <ul class="space-y-3" aria-hidden="true">
              <li
                v-for="(n, i) in notifications"
                :key="n.title"
                class="flex items-center gap-4 rounded-lg border border-border-default bg-surface p-4 shadow-sm transition-[opacity,translate] duration-500 ease-out dark:border-dark-border dark:bg-dark-surface"
                :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                :style="{ transitionDelay: `${600 + i * 250}ms` }"
              >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
                  <component :is="n.icon" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-small font-semibold text-ink dark:text-dark-ink">{{ n.title }}</p>
                  <p class="truncate text-caption text-muted dark:text-dark-muted">{{ n.meta }}</p>
                </div>
                <span class="hidden shrink-0 sm:block"><BaseBadge variant="muted">{{ n.channel }}</BaseBadge></span>
              </li>
            </ul>
            <figcaption class="mt-3 text-caption text-muted dark:text-dark-muted">Sample notifications · users choose their channels in Notification settings</figcaption>
          </figure>
        </div>
      </div>
    </div>
  </section>
</template>
