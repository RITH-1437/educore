<script setup>
import {
  ArrowRight,
  Bell,
  BookOpen,
  CalendarDays,
  ClipboardList,
  FolderOpen,
  GraduationCap,
  Landmark,
  LayoutDashboard,
  Search,
  ShieldCheck,
  TrendingUp,
  Users,
} from '@lucide/vue'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

const bars = [62, 78, 46, 88, 71, 55, 92, 66]
const courses = [
  { name: 'Compiler Design', done: 74, color: 'bg-blue-600' },
  { name: 'Database Systems', done: 62, color: 'bg-sky-500' },
  { name: 'Operating Systems', done: 48, color: 'bg-teal-500' },
]
</script>

<template>
  <section id="top" class="relative overflow-hidden">
    <div class="bg-grid-slate absolute inset-0" aria-hidden="true" />
    <div
      class="absolute -top-32 left-1/2 h-[520px] w-[900px] -translate-x-1/2 rounded-full bg-blue-600/10 blur-3xl"
      aria-hidden="true"
    />
    <div class="absolute top-40 -right-24 h-64 w-64 rounded-full bg-sky-500/15 blur-3xl" aria-hidden="true" />
    <div class="absolute -left-24 bottom-10 h-64 w-64 rounded-full bg-teal-500/10 blur-3xl" aria-hidden="true" />

    <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 pt-32 pb-20 sm:px-6 lg:grid-cols-2 lg:gap-10 lg:pt-40 lg:pb-28">
      <div class="max-w-xl">
        <div
          class="edu-fade-up inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 shadow-sm"
          style="animation-delay: 0.05s"
        >
          <Landmark class="h-4 w-4 text-blue-600" />
          University Digital Administration Platform
        </div>

        <h1
          class="font-display edu-fade-up mt-6 text-5xl leading-[1.05] font-bold tracking-tight text-slate-950 sm:text-6xl"
          style="animation-delay: 0.15s"
        >
          One Platform.
          <span class="text-blue-600">Smarter Education.</span>
        </h1>

        <p
          class="edu-fade-up mt-6 text-lg leading-relaxed text-slate-600"
          style="animation-delay: 0.25s"
        >
          EduCore brings academic management, student services, administration, documents,
          communication, and institutional workflows together in one secure digital platform.
        </p>

        <div class="edu-fade-up mt-8 flex flex-wrap items-center gap-3" style="animation-delay: 0.35s">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
            @click="scrollToId('platform')"
          >
            Explore EduCore
            <ArrowRight class="h-4 w-4" />
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
            @click="scrollToId('modules')"
          >
            View Platform
          </button>
        </div>

        <dl class="edu-fade-up mt-10 grid grid-cols-3 gap-6 border-t border-slate-200 pt-8" style="animation-delay: 0.45s">
          <div>
            <dt class="text-sm text-slate-500">Core modules</dt>
            <dd class="font-display mt-1 text-2xl font-bold text-slate-950">16</dd>
          </div>
          <div>
            <dt class="text-sm text-slate-500">User roles</dt>
            <dd class="font-display mt-1 text-2xl font-bold text-slate-950">5</dd>
          </div>
          <div>
            <dt class="text-sm text-slate-500">Data domains</dt>
            <dd class="font-display mt-1 text-2xl font-bold text-slate-950">49</dd>
          </div>
        </dl>
      </div>

      <div class="edu-fade-up relative mx-auto w-full max-w-xl" style="animation-delay: 0.55s">
        <div class="relative">
          <div class="rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
            <div class="flex items-center gap-1.5 border-b border-slate-200 px-4 py-3">
              <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
              <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
              <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
              <div class="ml-4 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                <img :src="symbolMark" alt="" class="h-3.5 w-3.5 rounded" />
                EduCore
              </div>
              <div class="ml-auto flex items-center gap-1">
                <Search class="h-3.5 w-3.5 text-slate-400" />
                <Bell class="h-3.5 w-3.5 text-slate-400" />
              </div>
            </div>

            <div class="grid grid-cols-[40px_1fr]">
              <div class="flex flex-col items-center gap-4 border-r border-slate-100 py-4">
                <LayoutDashboard class="h-4 w-4 text-blue-600" />
                <BookOpen class="h-4 w-4 text-slate-300" />
                <Users class="h-4 w-4 text-slate-300" />
                <CalendarDays class="h-4 w-4 text-slate-300" />
                <ClipboardList class="h-4 w-4 text-slate-300" />
                <FolderOpen class="h-4 w-4 text-slate-300" />
              </div>

              <div class="space-y-4 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs font-medium text-slate-400">Academic Overview</p>
                    <p class="font-display text-sm font-bold text-slate-900">Second Semester — 2025</p>
                  </div>
                  <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                    Spring Session
                  </span>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                  <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-[11px] text-slate-500">Students</p>
                    <p class="font-display mt-0.5 text-lg font-bold text-slate-900">4,825</p>
                    <p class="text-[11px] font-medium text-teal-600">+8.2%</p>
                  </div>
                  <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-[11px] text-slate-500">Average GPA</p>
                    <p class="font-display mt-0.5 text-lg font-bold text-slate-900">3.42</p>
                    <p class="text-[11px] font-medium text-teal-600">+0.06</p>
                  </div>
                  <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-[11px] text-slate-500">Attendance</p>
                    <p class="font-display mt-0.5 text-lg font-bold text-slate-900">94%</p>
                    <p class="text-[11px] font-medium text-teal-600">+1.4%</p>
                  </div>
                </div>

                <div class="rounded-lg border border-slate-100 p-3.5">
                  <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-700">Enrollment by Faculty</p>
                    <TrendingUp class="h-3.5 w-3.5 text-sky-500" />
                  </div>
                  <div class="mt-3 flex items-end gap-1.5">
                    <div
                      v-for="(h, i) in bars"
                      :key="i"
                      class="flex-1 rounded-t-sm"
                      :class="i % 3 === 2 ? 'bg-teal-400/80' : i % 3 === 1 ? 'bg-sky-500/80' : 'bg-blue-600'"
                      :style="{ height: `${h * 0.42}px` }"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                  <div v-for="c in courses" :key="c.name" class="rounded-lg border border-slate-100 p-3">
                    <p class="truncate text-[11px] font-semibold text-slate-700">{{ c.name }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ c.done }} students</p>
                    <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-100">
                      <div class="h-full rounded-full" :class="c.color" :style="{ width: `${c.done}%` }" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div
            class="edu-float absolute -top-5 -right-3 hidden items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-lg sm:flex"
          >
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50">
              <Bell class="h-4 w-4 text-blue-600" />
            </span>
            <div>
              <p class="text-xs font-semibold text-slate-900">Spring session started</p>
              <p class="text-[11px] text-slate-500">Timetable published — just now</p>
            </div>
          </div>

          <div
            class="edu-float-slow absolute -bottom-6 -left-4 hidden rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg sm:block"
          >
            <div class="flex items-center gap-2">
              <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50">
                <ShieldCheck class="h-4 w-4 text-teal-600" />
              </span>
              <div>
                <p class="text-xs font-semibold text-slate-900">Document verified</p>
                <div class="flex items-center gap-1 text-[11px] font-medium text-teal-600">
                  <GraduationCap class="h-3 w-3" />
                  Academic transcript — QR validated
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>