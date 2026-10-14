<script setup>
import { CircleCheck, FileText, Globe, LoaderCircle, QrCode, Receipt, ShieldCheck, UserCheck } from '@lucide/vue'
import { onBeforeUnmount, ref, watch } from 'vue'
import QrMark from './QrMark.vue'
import Reveal from './Reveal.vue'
import { prefersReducedMotion, useInView } from './useInView'

// The real workflow (reports 22, 33, 40, 41) next to a clearly fictional sample:
// "Example University", a demo student, a SAMPLE watermark and a QR mark that
// encodes nothing.
const steps = [
  { icon: FileText, title: 'Request', text: 'A student requests a certificate, transcript, semester result or internship letter from their workspace.' },
  { icon: UserCheck, title: 'Review', text: "University staff — or the student's Department Admin — approve it or reject it with a reason." },
  { icon: Receipt, title: 'Fee, where one applies', text: 'Fee-bearing types issue an invoice; the PDF waits until it is paid or the fee is waived.' },
  { icon: ShieldCheck, title: 'Generate', text: 'The PDF is built from enrollments, approved grades and GPA, and stored privately.' },
  { icon: Globe, title: 'Verify', text: 'Anyone can scan its QR code: the public page answers Valid, Revoked or Not found, with the SHA-256 checksum.' },
]

const fields = [
  { label: 'Issued to', value: 'Demo Student' },
  { label: 'Program', value: 'BSc Computer Science' },
  { label: 'Issued by', value: 'Example University' },
]

const stage = ref(null)
const inView = useInView(stage, { threshold: 0.4 })
const checked = ref(false)
let timer = null

// The result card answers after the first scan pass.
watch(inView, (visible) => {
  if (!visible) return
  if (prefersReducedMotion()) {
    checked.value = true
    return
  }
  timer = setTimeout(() => {
    checked.value = true
  }, 1800)
})

onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <section id="documents" class="scroll-mt-20 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="documents-title">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
      <Reveal>
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Documents & verification</p>
        <h2 id="documents-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          Digital Documents with Clear Verification Workflows
        </h2>
        <p class="mt-4 text-body text-muted dark:text-dark-muted">
          Official documents are requested online and generated as PDFs from EduCore's own records — never typed by hand.
          Each PDF carries a QR code and a verification code, so an employer or another institution can check it.
        </p>

        <ol class="mt-8 space-y-4">
          <li v-for="(step, i) in steps" :key="step.title" class="flex gap-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
              <component :is="step.icon" class="h-4 w-4" aria-hidden="true" />
            </span>
            <div>
              <h3 class="text-small font-semibold text-primary-dark dark:text-dark-ink">
                <span class="text-muted tabular-nums dark:text-dark-muted">{{ i + 1 }}.</span> {{ step.title }}
              </h3>
              <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ step.text }}</p>
            </div>
          </li>
        </ol>
      </Reveal>

      <Reveal from="right">
        <figure ref="stage" class="mx-auto max-w-md">
          <div class="relative pb-24 sm:pb-16" aria-hidden="true">
            <div class="relative overflow-hidden rounded-xl border border-border-default bg-surface p-6 shadow-lg dark:border-dark-border dark:bg-dark-surface">
              <span class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <span class="font-display -rotate-12 text-display tracking-widest text-muted/10 dark:text-dark-muted/10">SAMPLE</span>
              </span>

              <div class="relative flex items-center justify-between gap-4 border-b border-border-default pb-4 dark:border-dark-border">
                <div>
                  <p class="font-display text-h4 text-primary-dark dark:text-dark-ink">Academic Transcript</p>
                  <p class="text-caption text-muted dark:text-dark-muted">Generated with EduCore</p>
                </div>
                <FileText class="h-6 w-6 text-primary dark:text-dark-primary" />
              </div>

              <dl class="relative mt-4 space-y-3">
                <div v-for="f in fields" :key="f.label" class="flex items-center justify-between gap-4 border-b border-dashed border-border-default pb-3 dark:border-dark-border">
                  <dt class="text-caption text-muted dark:text-dark-muted">{{ f.label }}</dt>
                  <dd class="text-small font-semibold text-ink dark:text-dark-ink">{{ f.value }}</dd>
                </div>
              </dl>

              <div class="relative mt-5 flex items-center gap-4">
                <!-- The mark stays dark-on-light in both themes, like a printed QR code. -->
                <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-md border border-border-default bg-surface p-2 dark:border-dark-border">
                  <QrMark class="h-full w-full text-primary-dark" />
                  <div v-if="inView" class="edu-scan absolute inset-x-2 h-0.5 rounded-pill bg-primary shadow-sm" />
                </div>
                <div class="min-w-0 space-y-1">
                  <p class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink">
                    <QrCode class="h-4 w-4 text-primary dark:text-dark-primary" /> Scan to verify
                  </p>
                  <p class="font-mono text-caption break-all text-muted dark:text-dark-muted">Code SAMPLE-7Q4K-2M9X</p>
                </div>
              </div>
            </div>

            <!-- The public verification page's answer, after the scan. -->
            <div class="absolute right-0 bottom-0 left-8 rounded-lg border border-border-default bg-surface p-4 shadow-md sm:left-auto sm:w-72 dark:border-dark-border dark:bg-dark-surface">
              <p class="text-caption font-semibold tracking-widest text-muted uppercase dark:text-dark-muted">Verification page</p>
              <Transition name="edu-panel" mode="out-in">
                <div v-if="checked" key="valid" class="mt-2 flex items-start gap-3">
                  <CircleCheck class="h-6 w-6 shrink-0 text-success" />
                  <div class="min-w-0">
                    <p class="text-small font-semibold text-ink dark:text-dark-ink">Valid document</p>
                    <p class="text-caption text-muted dark:text-dark-muted">Issued 5 Oct 2026 · not revoked</p>
                    <p class="truncate font-mono text-caption text-muted dark:text-dark-muted">SHA-256 3f9a…c21e</p>
                  </div>
                </div>
                <p v-else key="wait" class="mt-2 flex items-center gap-2 text-small text-muted dark:text-dark-muted">
                  <LoaderCircle class="h-4 w-4 motion-safe:animate-spin" /> Checking the code…
                </p>
              </Transition>
            </div>
          </div>
          <figcaption class="mt-4 text-center text-caption text-muted dark:text-dark-muted">
            Sample document and verification result for illustration — not an issued record. The QR mark encodes nothing.
          </figcaption>
        </figure>
      </Reveal>
    </div>
  </section>
</template>
