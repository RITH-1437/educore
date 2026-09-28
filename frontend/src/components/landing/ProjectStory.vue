<script setup>
import { Building2, Compass, FolderKanban, Users } from '@lucide/vue'
import Reveal from './Reveal.vue'

const facts = [
  { icon: Building2, title: 'Designed for university environments', text: 'Built around the real structure of universities, faculties, programs, and student life.' },
  { icon: Compass, title: 'Focused on Cambodian university workflows', text: 'Workflows shaped around how Cambodian universities operate day to day.' },
  { icon: FolderKanban, title: 'A modular monolith', text: 'Focused modules on one connected codebase — simple to operate, easy to extend.' },
  { icon: Users, title: 'Practical academic administration', text: 'Prioritizing the records, documents, and schedules staff actually manage.' },
]

const status = [
  { label: 'Core infrastructure, authentication & access', state: 'Implemented', tone: 'bg-teal-50 text-teal-700' },
  { label: 'Login experience & landing page', state: 'In progress', tone: 'bg-sky-50 text-sky-700' },
  { label: 'Academic, student & administrator modules', state: 'Planned', tone: 'bg-amber-50 text-amber-700' },
  { label: 'Analytics, internals & advanced tooling', state: 'Future', tone: 'bg-slate-100 text-slate-500' },
]
</script>

<template>
  <section id="story" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid items-start gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
        <Reveal>
          <p class="text-xs font-semibold tracking-[0.2em] text-blue-600 uppercase">Project Story</p>
          <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Built as a Digital Foundation for Cambodian Universities
          </h2>
          <p class="mt-4 text-lg leading-relaxed text-slate-600">
            EduCore was created as a university-focused digital administration platform with the goal of
            demonstrating how academic and administrative processes can be organized within one modern
            system.
          </p>

          <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <Reveal v-for="(f, i) in facts" :key="f.title" :delay="i * 90" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
              <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                <component :is="f.icon" class="h-4.5 w-4.5 text-blue-600" />
              </span>
              <h3 class="font-display mt-3 text-sm font-semibold text-slate-900">{{ f.title }}</h3>
              <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ f.text }}</p>
            </Reveal>
          </div>
        </Reveal>

        <Reveal from="right" class="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
          <div class="flex items-center justify-between">
            <h3 class="font-display text-base font-semibold text-slate-900">Project status</h3>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500">
              Working progress
            </span>
          </div>
          <p class="mt-2 text-sm text-slate-500">
            EduCore is built module by module. This page reflects the current state honestly.
          </p>
          <ul class="mt-6 space-y-3">
            <li
              v-for="s in status"
              :key="s.label"
              class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3.5"
            >
              <span class="text-sm font-medium text-slate-700">{{ s.label }}</span>
              <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" :class="s.tone">{{ s.state }}</span>
            </li>
          </ul>
          <p class="mt-5 text-sm text-slate-500">
            Built by
            <span class="font-semibold text-slate-800">Rin Nairith &amp; Lyhor</span> as a university
            digital administration platform.
          </p>
        </Reveal>
      </div>
    </div>
  </section>
</template>