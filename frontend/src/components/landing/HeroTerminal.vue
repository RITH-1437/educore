<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { prefersReducedMotion } from './useInView'

// Real Makefile targets of this repository and what they report. The test
// count is a snapshot (2026-10-05, docs/37). Typed once, then rests on a prompt.
const script = [
  {
    cmd: 'make up',
    out: [
      [['ok', '✔'], ['ink', ' postgres  '], ['ok', '✔'], ['ink', ' redis  '], ['ok', '✔'], ['ink', ' minio']],
      [['ok', '✔'], ['ink', ' backend   '], ['ok', '✔'], ['ink', ' queue  '], ['ok', '✔'], ['ink', ' nginx']],
    ],
  },
  {
    cmd: 'make migrate && make seed',
    out: [
      [['info', 'INFO'], ['ink', ' Running migrations '], ['muted', '..... '], ['ok', 'DONE']],
      [['info', 'INFO'], ['ink', ' Seeding database '], ['muted', '....... '], ['ok', 'DONE']],
    ],
  },
  {
    cmd: 'make test',
    out: [[['ink', 'Tests:  '], ['ok', '411 passed'], ['muted', ' (3847 assertions)']]],
  },
  {
    cmd: 'open http://localhost',
    out: [
      [['ok', '✔'], ['ink', ' EduCore is running']],
      [['info', '  One Platform. Smarter Education.']],
    ],
  },
]

const tones = { ok: 'text-accent', info: 'text-secondary', muted: 'text-dark-muted', ink: 'text-dark-ink' }

const lines = ref([])
const typing = ref('')
const done = ref(false)
let cancelled = false
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

const allLines = () => script.flatMap((step) => [{ cmd: step.cmd }, ...step.out.map((segments) => ({ segments }))])

onMounted(async () => {
  if (prefersReducedMotion()) {
    lines.value = allLines()
    done.value = true
    return
  }
  await wait(1000) // let the hero copy land first
  for (const step of script) {
    for (const ch of step.cmd) {
      if (cancelled) return
      typing.value += ch
      await wait(45)
    }
    await wait(250)
    lines.value.push({ cmd: step.cmd })
    typing.value = ''
    for (const segments of step.out) {
      if (cancelled) return
      lines.value.push({ segments })
      await wait(140)
    }
    await wait(400)
  }
  done.value = true
})

onBeforeUnmount(() => {
  cancelled = true
})
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-dark-border bg-dark-bg text-left shadow-lg">
    <div class="flex items-center gap-2 border-b border-dark-border bg-primary-dark px-4 py-3" aria-hidden="true">
      <span class="h-3 w-3 rounded-pill bg-dark-border" />
      <span class="h-3 w-3 rounded-pill bg-dark-border" />
      <span class="h-3 w-3 rounded-pill bg-dark-border" />
      <span class="mx-auto font-mono text-caption text-dark-muted">~/educore — zsh</span>
      <span class="w-12" />
    </div>

    <div class="min-h-64 space-y-1 p-4 font-mono text-caption sm:min-h-80 sm:p-5 sm:text-small" aria-hidden="true">
      <p v-for="(line, i) in lines" :key="i" class="whitespace-pre-wrap">
        <template v-if="line.cmd"><span class="text-secondary">~/educore</span> <span class="text-accent">$</span> <span class="text-dark-ink">{{ line.cmd }}</span></template>
        <template v-else><span v-for="([tone, text], j) in line.segments" :key="j" :class="tones[tone]">{{ text }}</span></template>
      </p>
      <p class="whitespace-pre-wrap">
        <span class="text-secondary">~/educore</span> <span class="text-accent">$</span> <span class="text-dark-ink">{{ typing }}</span><span
          class="ml-px inline-block h-4 w-2 translate-y-0.5 bg-dark-ink"
          :class="done ? 'edu-blink' : ''"
        />
      </p>
    </div>

    <p class="sr-only">
      A terminal starts EduCore's Docker services, migrates and seeds the database, runs the test suite with
      411 passing tests, and opens the running application.
    </p>
  </div>
</template>
