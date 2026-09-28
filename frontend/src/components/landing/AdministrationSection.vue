<script setup>
import {
  Bell,
  CalendarDays,
  ClipboardCheck,
  FolderOpen,
  GraduationCap,
  Inbox,
  Landmark,
  LayoutDashboard,
  Search,
  Settings,
  Users,
} from '@lucide/vue'
import Reveal from './Reveal.vue'

const tasks = [
  'Student records',
  'Faculty management',
  'Department management',
  'Course management',
  'Enrollment',
  'Scheduling',
  'Attendance',
  'Document requests',
  'Invoices',
  'Announcements',
  'Reports',
]

const rows = [
  { student: 'Chan Sreypich', action: 'Enrollment update', status: 'Complete', tone: 'text-teal-600 bg-teal-50' },
  { student: 'Sok Dara', action: 'Transcript request', status: 'In review', tone: 'text-amber-600 bg-amber-50' },
  { student: 'Meas Vannak', action: 'Attendance entry', status: 'Saved', tone: 'text-slate-600 bg-slate-100' },
  { student: 'Lim Rithea', action: 'Syllabus publish', status: 'Sent', tone: 'text-teal-600 bg-teal-50' },
]
</script>

<template>
  <section id="admin" class="scroll-mt-24 bg-slate-50 py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
        <Reveal>
          <p class="text-xs font-semibold tracking-[0.2em] text-blue-600 uppercase">Administrative Experience</p>
          <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Less Manual Work. More Connected Operations.
          </h2>
          <p class="mt-4 text-lg leading-relaxed text-slate-600">
            Administrative teams handle records, schedules, and documents every day. EduCore gives staff a
            single, organized workspace for the operations that keep a university running.
          </p>
          <div class="mt-7 flex flex-wrap gap-2">
            <span
              v-for="t in tasks"
              :key="t"
              class="rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700"
            >
              {{ t }}
            </span>
          </div>
        </Reveal>

        <Reveal from="right" class="relative">
          <div class="rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
            <div class="flex items-center gap-1.5 border-b border-slate-200 px-4 py-3">
              <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
              <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
              <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
              <span class="ml-3 text-xs font-semibold text-slate-500">Administration workspace</span>
              <div class="ml-auto flex items-center gap-2">
                <Search class="h-3.5 w-3.5 text-slate-400" />
                <Settings class="h-3.5 w-3.5 text-slate-400" />
              </div>
            </div>

            <div class="grid sm:grid-cols-[150px_1fr]">
              <div class="hidden space-y-1 border-r border-slate-100 p-3 sm:block">
                <p v-for="(a, i) in ['Overview', 'Enrollment', 'Scheduling', 'Documents', 'Reports']" :key="a" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium" :class="i === 0 ? 'bg-blue-600 text-white' : 'text-slate-500'">
                  <component :is="[LayoutDashboard, ClipboardCheck, CalendarDays, FolderOpen, GraduationCap][i]" class="h-3.5 w-3.5" />
                  {{ a }}
                </p>
              </div>

              <div class="p-4 sm:p-5">
                <div class="flex items-center justify-between">
                  <p class="font-display text-sm font-bold text-slate-900">Recent operations</p>
                  <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">4 open items</span>
                </div>
                <ul class="mt-4 divide-y divide-slate-100">
                  <li v-for="r in rows" :key="r.action" class="flex items-center justify-between gap-3 py-3">
                    <div class="flex items-center gap-3">
                      <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100">
                        <Users class="h-4 w-4 text-slate-400" />
                      </span>
                      <div>
                        <p class="text-sm font-semibold text-slate-800">{{ r.student }}</p>
                        <p class="text-xs text-slate-500">{{ r.action }}</p>
                      </div>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="r.tone">{{ r.status }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="edu-float absolute -right-3 -bottom-5 hidden items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-lg md:flex">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50">
              <Bell class="h-4 w-4 text-teal-600" />
            </span>
            <div>
              <p class="text-xs font-semibold text-slate-900">2 announcements</p>
              <p class="text-[11px] text-slate-500">Delivered to all students</p>
            </div>
          </div>
          <div class="edu-float-slow absolute -top-4 -left-4 hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 shadow-lg md:flex">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50">
              <Inbox class="h-3.5 w-3.5 text-blue-600" />
            </span>
            <p class="text-xs font-semibold text-slate-800">5 pending document requests</p>
          </div>
        </Reveal>
      </div>
    </div>
  </section>
</template>