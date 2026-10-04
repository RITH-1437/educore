<script setup>
import {
  BellRing,
  Database,
  FileSpreadsheet,
  Files,
  Hand,
  Layers,
  MessagesSquare,
  Network,
  Unlink,
  Workflow,
} from '@lucide/vue'
import { onBeforeUnmount, ref, watch } from 'vue'
import SectionHeading from './SectionHeading.vue'
import { prefersReducedMotion, useInView } from './useInView'

// Each problem turns into the solution EduCore gives it.
const pairs = [
  { before: 'Paperwork', beforeIcon: Files, after: 'Digital workflows', afterIcon: Workflow, mess: 'translate(-14%, 22px) rotate(-5deg)' },
  { before: 'Spreadsheets', beforeIcon: FileSpreadsheet, after: 'Centralized information', afterIcon: Database, mess: 'translate(18%, -6px) rotate(4deg)' },
  { before: 'Scattered data', beforeIcon: Unlink, after: 'Connected academic data', afterIcon: Network, mess: 'translate(-6%, 10px) rotate(-3deg)' },
  { before: 'Manual processes', beforeIcon: Hand, after: 'Structured administration', afterIcon: Layers, mess: 'translate(22%, 4px) rotate(6deg)' },
  { before: 'Different communication channels', beforeIcon: MessagesSquare, after: 'Notifications', afterIcon: BellRing, mess: 'translate(-10%, -14px) rotate(-4deg)' },
]

const stage = ref(null)
const inView = useInView(stage, { threshold: 0.35 })
const solved = ref(false)
let timer = null

// Show the mess briefly, then let EduCore organise it (once, on first view).
watch(inView, (visible) => {
  if (!visible) return
  if (prefersReducedMotion()) {
    solved.value = true
    return
  }
  timer = setTimeout(() => {
    solved.value = true
  }, 700)
})

onBeforeUnmount(() => clearTimeout(timer))

const toggleBase = 'min-h-10 rounded-md px-4 text-label transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary'
</script>

<template>
  <section id="solution" class="scroll-mt-24 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="solution-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="solution-title"
        eyebrow="Problem → Solution"
        title="From scattered processes to one connected system"
        description="Many universities still run on paper forms, spreadsheets and messages spread across different apps. EduCore replaces each of those with a structured, digital workflow."
      />

      <div class="mt-10 flex justify-center">
        <div class="inline-flex gap-1 rounded-lg border border-border-default bg-surface p-1 shadow-sm dark:border-dark-border dark:bg-dark-surface" role="group" aria-label="Compare before and with EduCore">
          <button
            type="button"
            :class="[toggleBase, !solved ? 'bg-primary-dark text-white dark:bg-dark-surface-2' : 'text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink']"
            :aria-pressed="!solved"
            @click="solved = false"
          >
            Before EduCore
          </button>
          <button
            type="button"
            :class="[toggleBase, solved ? 'bg-primary text-white dark:bg-dark-primary dark:text-dark-bg' : 'text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink']"
            :aria-pressed="solved"
            @click="solved = true"
          >
            With EduCore
          </button>
        </div>
      </div>

      <div
        ref="stage"
        class="mx-auto mt-10 max-w-xl rounded-xl border p-4 transition-colors duration-500 ease-out sm:p-6"
        :class="solved ? 'border-primary/20 bg-primary/5 dark:border-dark-primary/30 dark:bg-dark-primary/10' : 'border-dashed border-border-muted bg-transparent dark:border-dark-border'"
        aria-live="polite"
      >
        <ul class="space-y-3">
          <li
            v-for="(pair, i) in pairs"
            :key="pair.before"
            class="flex items-center gap-3 rounded-lg border px-4 py-3 transition-[transform,background-color,border-color,box-shadow] duration-700 ease-out"
            :class="solved ? 'border-primary/20 bg-surface shadow-sm dark:border-dark-primary/30 dark:bg-dark-surface' : 'border-border-muted bg-background dark:border-dark-border dark:bg-dark-bg'"
            :style="{ transform: solved ? 'none' : pair.mess, transitionDelay: `${i * 90}ms` }"
          >
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md transition-colors duration-500"
              :class="solved ? 'bg-primary text-white dark:bg-dark-primary dark:text-dark-bg' : 'bg-border-default text-muted dark:bg-dark-border dark:text-dark-muted'"
              aria-hidden="true"
            >
              <component :is="solved ? pair.afterIcon : pair.beforeIcon" class="h-4 w-4" />
            </span>
            <span class="grid min-w-0 flex-1">
              <span
                class="col-start-1 row-start-1 text-small font-medium text-muted line-through decoration-error/60 transition-opacity duration-300 dark:text-dark-muted"
                :class="solved ? 'opacity-0' : 'opacity-100'"
                :aria-hidden="solved"
              >
                {{ pair.before }}
              </span>
              <span
                class="col-start-1 row-start-1 text-small font-semibold text-primary-dark transition-opacity duration-300 dark:text-dark-ink"
                :class="solved ? 'opacity-100' : 'opacity-0'"
                :aria-hidden="!solved"
              >
                {{ pair.after }}
              </span>
            </span>
          </li>
        </ul>

        <p class="mt-6 text-center text-small text-muted dark:text-dark-muted">
          <template v-if="solved">One platform, one set of records, and each role's work in its own place.</template>
          <template v-else>Information re-typed between offices, files and chat groups.</template>
        </p>
      </div>
    </div>
  </section>
</template>
