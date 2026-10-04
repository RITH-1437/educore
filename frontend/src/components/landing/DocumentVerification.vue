<script setup>
import { CircleCheck, FileText, Hash, LoaderCircle, QrCode, ShieldCheck } from '@lucide/vue'
import { onBeforeUnmount, ref, watch } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import QrMark from './QrMark.vue'
import Reveal from './Reveal.vue'
import { prefersReducedMotion, useInView } from './useInView'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

const fields = [
  { label: 'Student', value: 'Rin Nairith' },
  { label: 'Document', value: 'Academic Transcript' },
  { label: 'Issued on', value: '5 October 2026' },
  { label: 'Issuer', value: 'Office of the Registrar' },
]

const steps = [
  { icon: FileText, title: 'Request online', text: 'The student requests a transcript or certificate from their workspace.' },
  { icon: CircleCheck, title: 'Approve and issue', text: 'Staff approve it and EduCore generates the PDF from the official records.' },
  { icon: Hash, title: 'Sealed with a QR code', text: 'Each PDF carries a unique verification code, a QR code and a SHA-256 checksum.' },
  { icon: ShieldCheck, title: 'Verify anywhere', text: 'Anyone can scan the code. A public page answers Valid, Revoked or Not found.' },
]

const card = ref(null)
const inView = useInView(card, { threshold: 0.4 })
const verified = ref(false)
let timer = null

// Flip to "Verified" after the first scan pass.
watch(inView, (visible) => {
  if (!visible) return
  if (prefersReducedMotion()) {
    verified.value = true
    return
  }
  timer = setTimeout(() => {
    verified.value = true
  }, 1800)
})

onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <section id="documents" class="scroll-mt-24 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="documents-title">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
      <Reveal>
        <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Digital documents</p>
        <h2 id="documents-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
          Official documents anyone can verify
        </h2>
        <p class="mt-4 text-body text-muted dark:text-dark-muted">
          Transcripts, student certificates and internship letters are issued as PDFs straight from EduCore's
          records. A QR code on every document lets an employer or another university confirm it in seconds.
        </p>

        <ol class="mt-8 space-y-5">
          <li v-for="(step, i) in steps" :key="step.title" class="flex gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
              <component :is="step.icon" class="h-5 w-5" aria-hidden="true" />
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
        <figure ref="card" class="mx-auto max-w-md">
          <div class="rounded-xl border border-border-default bg-surface p-6 shadow-lg dark:border-dark-border dark:bg-dark-surface" aria-hidden="true">
            <div class="flex items-center justify-between gap-4 border-b border-border-default pb-4 dark:border-dark-border">
              <div class="flex items-center gap-3">
                <img :src="symbolMark" alt="" width="32" height="32" class="h-8 w-8 rounded-md object-cover" />
                <div>
                  <p class="font-display text-small font-bold text-primary-dark dark:text-dark-ink">Academic Transcript</p>
                  <p class="text-caption text-muted dark:text-dark-muted">Issued with EduCore</p>
                </div>
              </div>
              <Transition name="edu-panel" mode="out-in">
                <BaseBadge v-if="verified" key="ok" variant="success" size="md">
                  <CircleCheck class="h-4 w-4" /> Verified
                </BaseBadge>
                <BaseBadge v-else key="wait" variant="muted" size="md">
                  <LoaderCircle class="h-4 w-4 motion-safe:animate-spin" /> Checking…
                </BaseBadge>
              </Transition>
            </div>

            <dl class="mt-4 space-y-3">
              <div v-for="f in fields" :key="f.label" class="flex items-center justify-between gap-4 border-b border-dashed border-border-default pb-3 dark:border-dark-border">
                <dt class="text-caption text-muted dark:text-dark-muted">{{ f.label }}</dt>
                <dd class="text-small font-semibold text-ink dark:text-dark-ink">{{ f.value }}</dd>
              </div>
            </dl>

            <div class="mt-6 flex items-center gap-5">
              <div class="relative h-28 w-28 shrink-0 overflow-hidden rounded-md border border-border-default bg-surface p-2 dark:border-dark-border">
                <QrMark class="h-full w-full text-primary-dark" />
                <div
                  v-if="inView"
                  class="edu-scan absolute inset-x-2 h-0.5 rounded-pill bg-primary shadow-sm dark:bg-dark-primary"
                />
              </div>
              <div class="space-y-2">
                <p class="flex items-center gap-2 text-small font-semibold text-ink dark:text-dark-ink">
                  <QrCode class="h-4 w-4 text-primary dark:text-dark-primary" /> Scan to verify
                </p>
                <p class="text-caption text-muted dark:text-dark-muted">Opens the university's verification page with the issue date, the issuer and the PDF's SHA-256 checksum.</p>
              </div>
            </div>
          </div>
          <figcaption class="mt-4 text-center text-caption text-muted dark:text-dark-muted">Sample document · illustrative QR mark</figcaption>
        </figure>
      </Reveal>
    </div>
  </section>
</template>
