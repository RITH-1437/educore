<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight } from '@lucide/vue'
import Reveal from './Reveal.vue'
import { scrollToId } from './scrollTo'

// On navy the light-theme primary button would hover into the background, so
// these follow the dark-mode button tokens (DESIGN-TOKENS §3).
const base = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md px-5 py-3 text-button transition duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary active:scale-[0.98] sm:w-auto'
</script>

<template>
  <section id="cta" class="relative scroll-mt-24 overflow-hidden bg-primary-dark py-24 dark:border-t dark:border-dark-border" aria-labelledby="cta-title">
    <div class="landing-glow pointer-events-none absolute inset-0" aria-hidden="true" />

    <Reveal class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
      <h2 id="cta-title" class="font-display text-h1 text-balance text-dark-ink sm:text-display">Ready to explore EduCore?</h2>
      <p class="mx-auto mt-4 max-w-xl text-body text-dark-muted">
        See how academic management, student services, administration and communication work together on one platform.
      </p>
      <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
        <button type="button" :class="[base, 'bg-dark-primary text-dark-bg hover:bg-dark-ink']" @click="scrollToId('platform')">
          Explore Platform
          <ArrowRight class="h-4 w-4" aria-hidden="true" />
        </button>
        <Link href="/login" :class="[base, 'border border-dark-border text-dark-ink hover:bg-dark-surface-2']">Sign In</Link>
      </div>
    </Reveal>
  </section>
</template>
