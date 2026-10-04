<script setup>
import { Compass, Lightbulb, Target } from '@lucide/vue'
import Reveal from './Reveal.vue'

const story = [
  {
    icon: Target,
    title: 'What EduCore is',
    text: 'A university digital administration platform: academic structure, student services, administration, documents and communication in one secure web application.',
  },
  {
    icon: Lightbulb,
    title: 'Why we built it',
    text: 'Many universities still depend on paper forms, spreadsheets and disconnected tools. Students queue at offices for documents, and staff re-type the same records. We wanted to show that one well-structured platform can replace that.',
  },
  {
    icon: Compass,
    title: 'Cambodian university focus',
    text: 'EduCore is designed around how Cambodian universities are organised and run day to day, from faculties and programs to semesters, invoices and official documents.',
  },
]

const team = [
  { name: 'Rin Nairith', photo: '/assets/images/people/nairith.jpg' },
  { name: 'Yong Lyhor', photo: '/assets/images/people/lyhor.png' },
]
</script>

<template>
  <section id="about" class="scroll-mt-24 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="about-title">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[3fr_2fr] lg:gap-16 lg:px-8">
      <Reveal>
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Project story</p>
        <h2 id="about-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          Built for the future of university administration.
        </h2>

        <div class="mt-8 space-y-6">
          <div v-for="item in story" :key="item.title" class="flex gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
              <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
            </span>
            <div>
              <h3 class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ item.title }}</h3>
              <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ item.text }}</p>
            </div>
          </div>
        </div>
      </Reveal>

      <Reveal from="right" class="self-start rounded-xl border border-border-default bg-surface p-6 shadow-sm dark:border-dark-border dark:bg-dark-surface">
        <p class="text-caption font-semibold tracking-widest text-muted uppercase dark:text-dark-muted">The team</p>
        <ul class="mt-5 grid grid-cols-2 gap-4">
          <li v-for="member in team" :key="member.name" class="flex flex-col items-center text-center">
            <img
              :src="member.photo"
              :alt="`Portrait of ${member.name}`"
              width="96"
              height="96"
              loading="lazy"
              decoding="async"
              class="h-24 w-24 rounded-pill object-cover object-top ring-4 ring-primary/10 dark:ring-dark-primary/20"
            />
            <p class="mt-3 text-small font-semibold text-primary-dark dark:text-dark-ink">{{ member.name }}</p>
            <p class="text-caption text-muted dark:text-dark-muted">Developer</p>
          </li>
        </ul>
        <p class="mt-4 text-small text-muted dark:text-dark-muted">EduCore is our thesis and portfolio project.</p>

      </Reveal>
    </div>
  </section>
</template>
