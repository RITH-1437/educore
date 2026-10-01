<script setup>
import { Layers, Network, Scale, ShieldCheck } from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const highlights = [
  {
    icon: Layers,
    title: 'Centralized',
    text: 'Academic and administrative workflows live in one connected platform.',
  },
  {
    icon: ShieldCheck,
    title: 'Secure',
    text: 'Designed with role-based access and controlled data handling from the start.',
  },
  {
    icon: Network,
    title: 'Connected',
    text: 'Records, workflows, and people stay linked across every part of the institution.',
  },
  {
    icon: Scale,
    title: 'Scalable',
    text: 'A modular structure built to grow with the university as needs evolve.',
  },
]
</script>

<template>
  <section class="relative overflow-hidden bg-slate-950 py-20 sm:py-24">
    <div class="bg-grid-dark absolute inset-0" aria-hidden="true" />
    <div class="absolute top-0 left-1/2 h-72 w-[720px] -translate-x-1/2 rounded-full bg-blue-600/15 blur-3xl" aria-hidden="true" />

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        dark
        title="Built for the Modern University"
        description="Universities manage thousands of academic records, students, lecturers, courses, documents, schedules, assessments, and administrative processes. EduCore brings these operations together into one connected digital environment."
      />

      <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Reveal
          v-for="(item, i) in highlights"
          :key="item.title"
          :delay="i * 110"
          class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:bg-white/[0.07]"
        >
          <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-500/15">
            <component :is="item.icon" class="h-5 w-5 text-sky-400" />
          </span>
          <h3 class="font-display mt-4 text-lg font-semibold text-white">{{ item.title }}</h3>
          <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ item.text }}</p>
        </Reveal>
      </div>
    </div>
  </section>
</template>