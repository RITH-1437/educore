<script setup>
import { BookMarked, Building2, Calendar, CalendarRange, CircleCheck, GraduationCap, Landmark, Library, TableProperties } from '@lucide/vue'
import { ref } from 'vue'
import Reveal from './Reveal.vue'
import { useInView } from './useInView'

// The academic model as built (report 39: department-first, no faculty level).
// Two branches — organisation and calendar — meet in a section; students of a
// program enroll in sections. `x`/`y` place a node on the desktop diagram (%),
// `level` orders the progressive reveal.
const nodes = {
  university: { icon: Landmark, name: 'University', text: 'The institution and its current academic year', x: 50, y: 9, level: 0 },
  department: { icon: Building2, name: 'Department', text: 'Owns programs, courses and lecturers', x: 28, y: 36, level: 1 },
  year: { icon: CalendarRange, name: 'Academic year', text: 'The annual cycle the platform runs on', x: 76, y: 36, level: 1 },
  program: { icon: BookMarked, name: 'Program', text: 'A degree and its curriculum', x: 12, y: 63, level: 2 },
  course: { icon: Library, name: 'Course', text: 'Credits and prerequisites', x: 42, y: 63, level: 2 },
  semester: { icon: Calendar, name: 'Semester', text: 'Planned, open, closed, completed', x: 76, y: 63, level: 2 },
  student: { icon: GraduationCap, name: 'Student', text: 'Belongs to a program', x: 12, y: 90, level: 3, meet: true },
  section: { icon: TableProperties, name: 'Class / Section', text: 'A course offered in a semester', x: 59, y: 90, level: 3, meet: true },
}

const edges = [
  ['university', 'department'],
  ['university', 'year'],
  ['department', 'program'],
  ['department', 'course'],
  ['year', 'semester'],
  ['course', 'section'],
  ['semester', 'section'],
  ['program', 'student'],
]

const curve = ([from, to]) => {
  const a = nodes[from]
  const b = nodes[to]
  const mid = (a.y + b.y) / 2
  return `M ${a.x} ${a.y} C ${a.x} ${mid}, ${b.x} ${mid}, ${b.x} ${b.y}`
}

// Phones and tablets: the same model as an indented tree.
const tree = [
  { id: 'university', children: [{ id: 'department', children: ['program', 'course'] }, { id: 'year', children: ['semester'] }] },
  { id: 'section', children: [{ id: 'student', children: [] }] },
]

const facts = [
  'A grade knows its section, course, semester, program and department.',
  'Sections are where the structure and the calendar meet.',
  'Department Admins work only with their own department’s records.',
]

const diagram = ref(null)
const visible = useInView(diagram, { threshold: 0.25 })
const step = (level) => ({ transitionDelay: `${level * 220}ms` })
</script>

<template>
  <section id="how-it-works" class="scroll-mt-20 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="structure-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid gap-10 lg:grid-cols-[3fr_2fr] lg:items-end lg:gap-16">
        <Reveal>
          <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">How it works · Academic structure</p>
          <h2 id="structure-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
            Designed Around the Real Academic Structure
          </h2>
          <p class="mt-4 text-body text-muted dark:text-dark-muted">
            EduCore follows a department-first structure: departments sit directly under the university — there is no
            separate faculty level — and own their programs, courses and lecturers. The academic calendar runs alongside,
            and the two meet in a section: one course offered in one semester. Students belong to a program and enroll in
            sections.
          </p>
        </Reveal>
        <Reveal as="ul" :delay="100" class="space-y-3">
          <li v-for="fact in facts" :key="fact" class="flex gap-3 text-small text-ink dark:text-dark-ink">
            <CircleCheck class="mt-0.5 h-4 w-4 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
            {{ fact }}
          </li>
        </Reveal>
      </div>

      <!-- Desktop: the relationships as a diagram. -->
      <figure class="mt-12 hidden rounded-xl border border-border-default bg-surface p-6 shadow-sm lg:block dark:border-dark-border dark:bg-dark-surface">
        <div ref="diagram" class="relative aspect-[2/1]">
          <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <path
              v-for="edge in edges"
              :key="edge.join('-')"
              :d="curve(edge)"
              fill="none"
              vector-effect="non-scaling-stroke"
              class="stroke-border-muted transition-opacity duration-500 ease-out dark:stroke-dark-border"
              :class="visible ? 'opacity-100' : 'opacity-0'"
              :style="step(nodes[edge[1]].level)"
              stroke-width="1.5"
            />
            <path
              :d="`M ${nodes.student.x} ${nodes.student.y} L ${nodes.section.x} ${nodes.section.y}`"
              fill="none"
              vector-effect="non-scaling-stroke"
              class="stroke-primary transition-opacity duration-500 ease-out dark:stroke-dark-primary"
              :class="visible ? 'opacity-100' : 'opacity-0'"
              :style="step(4)"
              stroke-width="1.5"
              stroke-dasharray="6 5"
            />
          </svg>

          <span
            class="absolute -translate-x-1/2 -translate-y-full rounded-pill bg-primary/10 px-3 py-1 text-caption font-semibold text-primary transition-opacity duration-500 dark:bg-dark-primary/15 dark:text-dark-primary"
            :class="visible ? 'opacity-100' : 'opacity-0'"
            :style="{ left: `${(nodes.student.x + nodes.section.x) / 2}%`, top: `${nodes.section.y - 2}%`, ...step(4) }"
            aria-hidden="true"
          >
            enrolls in
          </span>

          <ul class="absolute inset-0" aria-label="Academic structure">
            <li
              v-for="(node, id) in nodes"
              :key="id"
              class="absolute flex w-56 items-center gap-3 rounded-lg border bg-surface p-3 shadow-sm transition-[opacity,translate] duration-500 ease-out dark:bg-dark-surface"
              :class="[
                node.meet ? 'border-primary/40 dark:border-dark-primary/50' : 'border-border-default dark:border-dark-border',
                visible ? '-translate-x-1/2 -translate-y-1/2 opacity-100' : '-translate-x-1/2 -translate-y-1/3 opacity-0',
              ]"
              :style="{ left: `${node.x}%`, top: `${node.y}%`, ...step(node.level) }"
            >
              <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md"
                :class="node.meet ? 'bg-primary text-white dark:bg-dark-primary dark:text-dark-bg' : 'bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary'"
              >
                <component :is="node.icon" class="h-4 w-4" aria-hidden="true" />
              </span>
              <span class="min-w-0">
                <span class="block text-small font-semibold text-primary-dark dark:text-dark-ink">{{ node.name }}</span>
                <span class="block text-caption text-muted dark:text-dark-muted">{{ node.text }}</span>
              </span>
            </li>
          </ul>
        </div>
        <figcaption class="mt-4 text-caption text-muted dark:text-dark-muted">
          Lines show ownership. A student of a program enrolls in sections; attendance, coursework, exams and grades are recorded per enrollment.
        </figcaption>
      </figure>

      <!-- Phones and tablets: an indented tree. -->
      <div class="mt-10 space-y-4 lg:hidden">
        <Reveal v-for="root in tree" :key="root.id" as="ul" class="rounded-xl border border-border-default bg-surface p-4 dark:border-dark-border dark:bg-dark-surface">
          <li>
            <p class="flex items-center gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-white dark:bg-dark-primary dark:text-dark-bg">
                <component :is="nodes[root.id].icon" class="h-4 w-4" aria-hidden="true" />
              </span>
              <span>
                <span class="block text-small font-semibold text-primary-dark dark:text-dark-ink">{{ nodes[root.id].name }}</span>
                <span class="block text-caption text-muted dark:text-dark-muted">{{ nodes[root.id].text }}</span>
              </span>
            </p>
            <ul class="mt-3 ml-4 space-y-3 border-l border-border-muted pl-5 dark:border-dark-border">
              <li v-for="branch in root.children" :key="branch.id">
                <p class="flex items-center gap-3">
                  <component :is="nodes[branch.id].icon" class="h-4 w-4 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
                  <span>
                    <span class="block text-small font-semibold text-primary-dark dark:text-dark-ink">{{ nodes[branch.id].name }}</span>
                    <span class="block text-caption text-muted dark:text-dark-muted">{{ nodes[branch.id].text }}</span>
                  </span>
                </p>
                <ul v-if="branch.children.length" class="mt-3 ml-2 space-y-3 border-l border-border-muted pl-5 dark:border-dark-border">
                  <li v-for="leaf in branch.children" :key="leaf" class="flex items-center gap-3">
                    <component :is="nodes[leaf].icon" class="h-4 w-4 shrink-0 text-muted dark:text-dark-muted" aria-hidden="true" />
                    <span>
                      <span class="block text-small font-semibold text-ink dark:text-dark-ink">{{ nodes[leaf].name }}</span>
                      <span class="block text-caption text-muted dark:text-dark-muted">{{ nodes[leaf].text }}</span>
                    </span>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
        </Reveal>
        <p class="text-caption text-muted dark:text-dark-muted">
          A section is a course offered in a semester. A student of a program enrolls in sections; attendance, coursework,
          exams and grades are recorded per enrollment.
        </p>
      </div>
    </div>
  </section>
</template>
