<script setup>
import { Layers, Network, Puzzle, ScrollText, ShieldEllipsis, TrendingUp } from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const reasons = [
  { icon: Layers, title: 'Centralized', text: 'Academic and administrative workflows in one platform.' },
  { icon: Puzzle, title: 'Modular', text: 'Organized into focused modules that can evolve with university needs.' },
  { icon: ShieldEllipsis, title: 'Role-Based', text: 'Different university roles access the workflows relevant to them.' },
  { icon: Network, title: 'Connected', text: 'Academic data and workflows are connected across the platform.' },
  { icon: TrendingUp, title: 'Scalable Architecture', text: 'Structured to support future expansion.' },
  { icon: ScrollText, title: 'Digital-First', text: 'Reduces dependence on fragmented manual workflows.' },
]
</script>

<template>
  <section id="why" class="scroll-mt-24 bg-slate-50 py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading eyebrow="Why EduCore" title="Why EduCore?" description="A straightforward look at what EduCore is designed to do — no exaggerated promises." />

      <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Reveal v-for="(r, i) in reasons" :key="r.title" :delay="(i % 3) * 100" class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-6 transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md hover:shadow-slate-900/5">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors duration-200 group-hover:bg-blue-600 group-hover:text-white">
            <component :is="r.icon" class="h-5 w-5 text-blue-600 transition-colors duration-200 group-hover:text-white" />
          </span>
          <div>
            <h3 class="font-display text-base font-semibold text-slate-900 transition-colors duration-200 group-hover:text-blue-600">{{ r.title }}</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ r.text }}</p>
          </div>
        </Reveal>
      </div>
    </div>
  </section>
</template>