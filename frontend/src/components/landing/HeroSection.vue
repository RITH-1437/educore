<script setup>
import { ArrowRight, CircleCheck } from '@lucide/vue'
import BaseButton from '../BaseButton.vue'
import HeroTerminal from './HeroTerminal.vue'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

const facts = ['25 connected modules', '5 user roles', 'Built for Cambodian universities']

// Entrance order: badge → headline → copy → each CTA → facts → terminal.
const delay = (seconds) => ({ animationDelay: `${seconds}s` })
</script>

<template>
  <section id="top" class="relative overflow-hidden bg-background dark:bg-dark-bg" aria-labelledby="hero-title">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="app-ambient edu-drift absolute -inset-16" />
      <div class="bg-grid-slate absolute inset-0" />
    </div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pt-32 pb-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pt-40 lg:pb-24">
      <div class="max-w-xl">
        <p
          class="edu-fade-up inline-flex items-center gap-2 rounded-pill border border-border-default bg-surface py-1 pr-3 pl-1 text-small font-medium text-ink shadow-sm dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink"
          :style="delay(0.05)"
        >
          <img :src="symbolMark" alt="" width="24" height="24" class="h-6 w-6 rounded-pill object-cover" />
          <span><span class="font-semibold text-primary-dark dark:text-dark-ink">EduCore</span> · University Digital Administration</span>
        </p>

        <h1 id="hero-title" class="font-display edu-fade-up mt-6 text-h1 text-primary-dark sm:text-display dark:text-dark-ink" :style="delay(0.15)">
          One Platform.
          <span class="block text-primary dark:text-dark-primary">Smarter Education.</span>
        </h1>

        <p class="edu-fade-up mt-6 text-body text-muted sm:text-h4 sm:font-normal dark:text-dark-muted" :style="delay(0.25)">
          EduCore runs a university on one secure platform: academic structure, student services,
          administration, documents and communication, connected for every role.
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-3">
          <BaseButton size="lg" class="edu-fade-up" :style="delay(0.35)" @click="scrollToId('platform')">
            Explore Platform
            <ArrowRight class="h-4 w-4" aria-hidden="true" />
          </BaseButton>
          <BaseButton href="/login" variant="secondary" size="lg" class="edu-fade-up" :style="delay(0.45)">
            Sign In
          </BaseButton>
        </div>

        <ul class="edu-fade-up mt-10 flex flex-wrap gap-x-6 gap-y-2 border-t border-border-default pt-6 text-small text-muted dark:border-dark-border dark:text-dark-muted" :style="delay(0.55)">
          <li v-for="fact in facts" :key="fact" class="flex items-center gap-2">
            <CircleCheck class="h-4 w-4 text-primary dark:text-dark-primary" aria-hidden="true" />
            {{ fact }}
          </li>
        </ul>
      </div>

      <figure class="edu-slide-in mx-auto w-full max-w-2xl" :style="delay(0.5)">
        <HeroTerminal />
        <figcaption class="mt-4 text-center text-caption text-muted dark:text-dark-muted">
          Real commands from the EduCore repository · test count as of October 2026
        </figcaption>
      </figure>
    </div>
  </section>
</template>
