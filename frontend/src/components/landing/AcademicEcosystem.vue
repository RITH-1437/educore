<script setup>
import {
  BookMarked,
  Building2,
  Calendar,
  CalendarRange,
  FolderTree,
  GraduationCap,
  Landmark,
  Library,
  TableProperties,
} from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import Reveal from './Reveal.vue'
import { prefersReducedMotion } from './useInView'

const levels = [
  { icon: Landmark, name: 'University', tag: 'The institution and its current academic year' },
  { icon: Building2, name: 'Faculty', tag: 'Academic units, each with its own Faculty Admins' },
  { icon: FolderTree, name: 'Department', tag: 'Disciplines that own programs and lecturers' },
  { icon: BookMarked, name: 'Program', tag: 'Degrees and the courses in their curricula' },
  { icon: CalendarRange, name: 'Academic Year', tag: 'The annual cycle the platform runs on' },
  { icon: Calendar, name: 'Semester', tag: 'Teaching terms that bound grades and attendance' },
  { icon: Library, name: 'Course', tag: 'Subjects with credits and prerequisites' },
  { icon: TableProperties, name: 'Section', tag: 'A course offered this semester, with room and lecturers' },
  { icon: GraduationCap, name: 'Student', tag: 'Enrolled in sections: attendance, grades and GPA' },
]

const track = ref(null)
const nodes = ref([])
const fill = ref(0) // 0–100 % of the track
const reached = ref(-1)
let frame = 0

// The line fills up to a point 60% down the viewport; every node above that
// point is "reached". Scroll-linked, measured at most once per frame.
const measure = () => {
  frame = 0
  if (!track.value) return
  const anchor = window.innerHeight * 0.6
  const rect = track.value.getBoundingClientRect()
  fill.value = Math.min(Math.max(((anchor - rect.top) / rect.height) * 100, 0), 100)
  reached.value = nodes.value.reduce((last, node, i) => {
    const r = node.getBoundingClientRect()
    return r.top + r.height / 2 <= anchor ? i : last
  }, -1)
}

const onScroll = () => {
  if (!frame) frame = requestAnimationFrame(measure)
}

onMounted(() => {
  if (prefersReducedMotion()) {
    fill.value = 100
    reached.value = levels.length - 1
    return
  }
  measure()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
  cancelAnimationFrame(frame)
})

const current = computed(() => levels[Math.max(reached.value, 0)])
</script>

<template>
  <section id="structure" class="scroll-mt-24 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="structure-title">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
      <Reveal class="lg:sticky lg:top-32 lg:self-start">
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Academic ecosystem</p>
        <h2 id="structure-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          Built on the real structure of a university
        </h2>
        <p class="mt-4 text-body text-muted dark:text-dark-muted">
          EduCore models the institution from the top down. Every record sits at one level and stays linked
          to the levels above it, so a student's grade always knows its section, course, semester, program
          and faculty.
        </p>

        <div class="mt-8 hidden items-center gap-4 rounded-xl border border-border-default bg-surface p-5 shadow-sm lg:flex dark:border-dark-border dark:bg-dark-surface" aria-hidden="true">
          <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary text-white dark:bg-dark-primary dark:text-dark-bg">
            <component :is="current.icon" class="h-6 w-6" />
          </span>
          <div>
            <p class="text-caption font-semibold tracking-widest text-muted uppercase dark:text-dark-muted">
              Level {{ Math.max(reached, 0) + 1 }} of {{ levels.length }}
            </p>
            <p class="font-display text-h4 text-primary-dark dark:text-dark-ink">{{ current.name }}</p>
          </div>
        </div>
      </Reveal>

      <div class="relative">
        <div ref="track" class="absolute top-5 bottom-5 left-5 w-0.5 -translate-x-1/2 rounded-pill bg-border-default dark:bg-dark-border" aria-hidden="true">
          <div class="absolute inset-x-0 top-0 rounded-pill bg-primary dark:bg-dark-primary" :style="{ height: `${fill}%` }" />
        </div>

        <ol class="relative space-y-4">
          <li v-for="(level, i) in levels" :key="level.name" class="flex items-center gap-4">
            <span
              :ref="(el) => { nodes[i] = el }"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill border-2 transition-colors duration-300 ease-out"
              :class="i <= reached ? 'border-primary bg-primary text-white dark:bg-dark-primary dark:text-dark-bg dark:border-dark-primary' : 'border-border-default bg-surface text-muted dark:border-dark-border dark:bg-dark-surface dark:text-dark-muted'"
            >
              <component :is="level.icon" class="h-4 w-4" aria-hidden="true" />
            </span>
            <div
              class="flex flex-1 items-center justify-between gap-4 rounded-lg border px-4 py-3 transition-[background-color,border-color,box-shadow] duration-300 ease-out"
              :class="i <= reached ? 'border-primary/20 bg-surface shadow-sm dark:border-dark-primary/30 dark:bg-dark-surface' : 'border-transparent'"
            >
              <div>
                <h3 class="text-small font-semibold transition-colors duration-300" :class="i <= reached ? 'text-primary-dark dark:text-dark-ink' : 'text-muted dark:text-dark-muted'">
                  {{ level.name }}
                </h3>
                <p class="text-caption text-muted dark:text-dark-muted">{{ level.tag }}</p>
              </div>
              <span class="hidden text-caption font-semibold text-muted tabular-nums sm:block dark:text-dark-muted" aria-hidden="true">L{{ i + 1 }}</span>
            </div>
          </li>
        </ol>
      </div>
    </div>
  </section>
</template>
