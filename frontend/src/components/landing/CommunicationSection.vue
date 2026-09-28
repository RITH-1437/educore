<script setup>
import { Bell, Building2, FolderOpen, GraduationCap, Landmark, Mail, Send, UserCog } from '@lucide/vue'
import Reveal from './Reveal.vue'

const tree = [
  { icon: Landmark, label: 'University' },
  { icon: Building2, label: 'Faculty' },
  { icon: FolderOpen, label: 'Department' },
  { icon: UserCog, label: 'Lecturers' },
  { icon: GraduationCap, label: 'Students' },
]

const channels = [
  { icon: Bell, title: 'In-platform', text: 'Priority updates inside the EduCore workspace.' },
  { icon: Mail, title: 'Email', text: 'Transactional and digest notifications by email.' },
  { icon: Send, title: 'Telegram', text: 'Notifications delivered where people already chat.' },
]
</script>

<template>
  <section id="communication" class="scroll-mt-24 bg-slate-50 py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
        <Reveal>
          <p class="text-xs font-semibold tracking-[0.2em] text-blue-600 uppercase">Communication</p>
          <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Keep Everyone Connected
          </h2>
          <p class="mt-4 text-lg leading-relaxed text-slate-600">
            News and updates flow through the university's real structure — from the institution down to
            every lecturer and student.
          </p>

          <div class="mt-8">
            <ol class="relative space-y-2.5">
              <div class="absolute top-3 bottom-3 left-[18px] w-px bg-slate-200" aria-hidden="true" />
              <li v-for="(n, i) in tree" :key="n.label" class="relative flex items-center gap-3.5">
                <span
                  class="relative z-10 flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm"
                >
                  <component :is="n.icon" class="h-4 w-4 text-blue-600" />
                </span>
                <span class="text-sm font-semibold text-slate-800">{{ n.label }}</span>
                <span v-if="i < tree.length - 1" class="ml-auto pr-2 text-slate-300">↓</span>
              </li>
            </ol>
            <div class="mt-5 flex flex-wrap gap-2">
              <span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">Targeted audience</span>
              <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">Right people, right level</span>
            </div>
          </div>
        </Reveal>

        <div class="grid gap-4">
          <Reveal v-for="(c, i) in channels" :key="c.title" :from="i % 2 ? 'left' : 'right'" :delay="i * 100">
            <div class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 transition duration-300 group-hover:bg-blue-600">
                <component :is="c.icon" class="h-5 w-5 text-slate-500 transition duration-300 group-hover:text-white" />
              </span>
              <div>
                <h3 class="font-display text-base font-semibold text-slate-900">{{ c.title }}</h3>
                <p class="text-sm text-slate-500">{{ c.text }}</p>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </div>
  </section>
</template>