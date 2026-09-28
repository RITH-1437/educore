<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight } from '@lucide/vue'
import Reveal from './Reveal.vue'
import { scrollToId } from './scrollTo'
</script>

<template>
  <section id="cta" class="scroll-mt-24 relative overflow-hidden bg-slate-950 py-24">
    <div class="bg-grid-dark absolute inset-0" aria-hidden="true" />
    <div class="absolute top-1/2 left-1/2 h-80 w-[860px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-600/20 blur-3xl" aria-hidden="true" />
    <div class="absolute -top-20 right-8 h-48 w-48 rounded-full bg-sky-500/10 blur-3xl" aria-hidden="true" />

    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
      <Reveal>
        <h2 class="font-display text-3xl font-bold tracking-tight text-white sm:text-5xl">
          Ready to Explore EduCore?
        </h2>
        <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-slate-400">
          Discover how academic management, student services, administration, communication, and digital
          workflows can work together in one platform.
        </p>
        <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <button
            type="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:w-auto"
            @click="scrollToId('platform')"
          >
            Explore Platform
            <ArrowRight class="h-4 w-4" />
          </button>
          <Link
            href="/login"
            class="w-full rounded-lg border border-white/15 bg-white/5 px-7 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:w-auto"
          >
            Sign In
          </Link>
        </div>
      </Reveal>
    </div>
  </section>
</template>