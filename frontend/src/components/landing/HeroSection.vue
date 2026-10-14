<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Building2, GraduationCap, Landmark, Presentation } from '@lucide/vue'
import BaseButton from '../BaseButton.vue'
import HeroPreview from './HeroPreview.vue'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

// Who EduCore is for: the people who sign in to it every day.
const audiences = [
  { icon: Landmark, label: 'University admins' },
  { icon: Building2, label: 'Department staff' },
  { icon: Presentation, label: 'Lecturers' },
  { icon: GraduationCap, label: 'Students' },
]

// Entrance order: badge → headline → copy → CTAs → audiences, then the preview.
const delay = (seconds) => ({ animationDelay: `${seconds}s` })
</script>

<template>
  <section id="top" class="relative overflow-hidden bg-background dark:bg-dark-bg" aria-labelledby="hero-title">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="app-ambient absolute inset-0" />
      <div class="bg-grid-slate landing-grid-fade absolute inset-0" />
    </div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pt-28 pb-16 sm:px-6 sm:pt-32 lg:grid-cols-[5fr_6fr] lg:px-8 lg:pt-40 lg:pb-24 xl:gap-16">
      <div class="max-w-xl">
        <p
          class="edu-fade-up inline-flex items-center gap-2 rounded-pill border border-border-default bg-surface py-1 pr-3 pl-1 text-small font-medium text-ink shadow-sm dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink"
          :style="delay(0.05)"
        >
          <img :src="symbolMark" alt="" width="24" height="24" class="h-6 w-6 rounded-pill object-cover" />
          University Digital Administration Platform
        </p>

        <h1 id="hero-title" class="font-display edu-fade-up mt-6 text-h1 text-primary-dark sm:text-display lg:text-h1 xl:text-display dark:text-dark-ink" :style="delay(0.15)">
          One Platform.
          <span class="block text-primary dark:text-dark-primary">Smarter Education.</span>
        </h1>

        <p class="edu-fade-up mt-6 text-body text-muted sm:text-h4 sm:font-normal lg:text-body xl:text-h4 xl:font-normal dark:text-dark-muted" :style="delay(0.25)">
          Bring academic management, student services, administration, communication, and digital workflows
          together in one connected university platform.
        </p>

        <div class="edu-fade-up mt-8 flex flex-col gap-3 sm:flex-row sm:items-center" :style="delay(0.35)">
          <BaseButton size="lg" class="whitespace-nowrap" @click="scrollToId('showcase')">
            Explore EduCore
            <ArrowRight class="h-4 w-4" aria-hidden="true" />
          </BaseButton>
          <BaseButton variant="secondary" size="lg" class="whitespace-nowrap" @click="scrollToId('platform')">Discover the Platform</BaseButton>
        </div>
        <p class="edu-fade-up mt-4 text-small text-muted dark:text-dark-muted" :style="delay(0.4)">
          Already have an account?
          <Link
            href="/login"
            class="rounded-sm font-medium text-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-dark-primary dark:focus-visible:outline-dark-primary"
          >
            Sign in
          </Link>
        </p>

        <div class="edu-fade-up mt-10 border-t border-border-default pt-6 dark:border-dark-border" :style="delay(0.5)">
          <p class="text-caption font-semibold tracking-widest text-muted uppercase dark:text-dark-muted">Designed for</p>
          <ul class="mt-3 grid max-w-md grid-cols-2 gap-x-6 gap-y-3">
            <li v-for="a in audiences" :key="a.label" class="flex items-center gap-2 text-small text-ink dark:text-dark-ink">
              <component :is="a.icon" class="h-4 w-4 text-primary dark:text-dark-primary" aria-hidden="true" />
              {{ a.label }}
            </li>
          </ul>
        </div>
      </div>

      <HeroPreview class="edu-slide-in" :style="delay(0.45)" />
    </div>
  </section>
</template>
