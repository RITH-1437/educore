<script setup>
import { Container, Cpu, Database, Globe, Server } from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const groups = [
  {
    name: 'Backend',
    icon: Server,
    tone: 'text-blue-600',
    bg: 'bg-blue-50',
    items: [
      { name: 'Laravel', role: 'Web framework' },
      { name: 'PHP', role: 'Runtime' },
      { name: 'PostgreSQL', role: 'Database' },
      { name: 'Redis', role: 'Cache & queues' },
    ],
  },
  {
    name: 'Frontend',
    icon: Cpu,
    tone: 'text-sky-600',
    bg: 'bg-sky-50',
    items: [
      { name: 'Vue 3', role: 'UI framework' },
      { name: 'Tailwind CSS', role: 'Styling' },
      { name: 'Pinia', role: 'State' },
      { name: 'Inertia.js', role: 'App glue' },
      { name: 'Chart.js', role: 'Charts' },
    ],
  },
  {
    name: 'Auth & Infrastructure',
    icon: Globe,
    tone: 'text-teal-600',
    bg: 'bg-teal-50',
    items: [
      { name: 'Laravel Sanctum', role: 'Auth' },
      { name: 'Docker', role: 'Containers' },
      { name: 'Nginx', role: 'Web server' },
      { name: 'MinIO', role: 'File storage' },
      { name: 'Laravel Cloud', role: 'Deployment' },
    ],
  },
]
</script>

<template>
  <section id="technology" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        eyebrow="Technology"
        title="Built With Modern Technology"
        description="EduCore runs on a pragmatic, modular stack — the technologies listed here are the ones actually used across the platform."
      />

      <div class="mt-14 grid gap-6 lg:grid-cols-3">
        <Reveal v-for="(g, gi) in groups" :key="g.name" :delay="gi * 120" class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
          <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
              <component :is="g.icon" class="h-5 w-5" :class="g.tone" />
            </span>
            <h3 class="font-display text-base font-semibold text-slate-900">{{ g.name }}</h3>
          </div>
          <div class="mt-5 grid grid-cols-2 gap-2.5">
            <div v-for="t in g.items" :key="t.name" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 transition duration-300 hover:border-blue-200">
              <p class="text-sm font-semibold text-slate-900">{{ t.name }}</p>
              <p class="text-xs text-slate-500">{{ t.role }}</p>
            </div>
          </div>
        </Reveal>
      </div>

      <Reveal :delay="200" class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-500">
        <span class="flex items-center gap-1.5">
          <Database class="h-4 w-4 text-slate-400" /> Modular monolith architecture
        </span>
        <span class="flex items-center gap-1.5">
          <Container class="h-4 w-4 text-slate-400" /> Dockerized development
        </span>
        <span class="flex items-center gap-1.5">
          <Globe class="h-4 w-4 text-slate-400" /> Deployable with Laravel Cloud
        </span>
      </Reveal>
    </div>
  </section>
</template>