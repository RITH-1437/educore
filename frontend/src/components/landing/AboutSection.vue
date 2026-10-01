<script setup>
import { ArrowRight, BellRing, CircleCheck, FileX2, Files, Link2Off, TimerOff } from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const before = [
  { icon: Files, text: 'Spreadsheets and paper documents' },
  { icon: Link2Off, text: 'Disconnected systems' },
  { icon: TimerOff, text: 'Manual administrative processes' },
  { icon: FileX2, text: 'Scattered institutional communication' },
]

const afterItems = [
  { icon: BellRing, text: 'Automated notifications' },
  { icon: CircleCheck, text: 'Connected academic data' },
  { icon: ArrowRight, text: 'Centralized platform' },
]
</script>

<template>
  <section id="about" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        eyebrow="About EduCore"
        title="From Fragmented Processes to One Connected Platform"
        description="EduCore is designed to simplify university operations by connecting academic management, student services, administrative workflows, communication, and institutional information in a single platform."
      />

      <div class="mt-14 grid gap-6 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch">
        <Reveal
          from="left"
          class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-8"
        >
          <div class="flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-slate-600">Before</h3>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500">
              Fragmented workflows
            </span>
          </div>
          <p class="mt-1 text-sm text-slate-500">How university operations often stay today.</p>
          <ul class="mt-6 space-y-3">
            <li
              v-for="item in before"
              :key="item.text"
              class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600"
            >
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                <component :is="item.icon" class="h-4 w-4 text-slate-400" />
              </span>
              {{ item.text }}
            </li>
          </ul>
        </Reveal>

        <div class="flex items-center justify-center lg:px-2" aria-hidden="true">
          <Reveal from="scale" class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 shadow-lg shadow-blue-600/30">
            <ArrowRight class="h-5 w-5 rotate-90 text-white lg:rotate-0" />
          </Reveal>
        </div>

        <Reveal from="right" class="relative overflow-hidden rounded-2xl border border-blue-100 bg-blue-50/60 p-8">
          <div class="flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-blue-900">After</h3>
            <span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">
              One connected platform
            </span>
          </div>
          <p class="mt-1 text-sm text-blue-700/70">Everything operated from a single digital environment.</p>
          <ul class="mt-6 space-y-3">
            <li
              v-for="item in afterItems"
              :key="item.text"
              class="flex items-center gap-3 rounded-lg border border-blue-100 bg-white px-4 py-3 text-sm font-medium text-slate-800"
            >
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600">
                <component :is="item.icon" class="h-4 w-4 text-white" />
              </span>
              {{ item.text }}
            </li>
          </ul>
        </Reveal>
      </div>
    </div>
  </section>
</template>