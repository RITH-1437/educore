<script setup>
import { BadgeCheck, CheckCircle2, FileText, FolderOpen, QrCode, ScanLine } from '@lucide/vue'
import Reveal from './Reveal.vue'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

const fields = [
  { label: 'Student', value: 'Example Student' },
  { label: 'Document', value: 'Academic Transcript' },
  { label: 'Issue date', value: '2026' },
  { label: 'Reference', value: 'EDCR-000123' },
]

const pipeline = ['Request', 'Review', 'Issue', 'Verify', 'QR']
</script>

<template>
  <section id="documents" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
        <Reveal>
          <p class="text-xs font-semibold tracking-[0.2em] text-blue-600 uppercase">Digital Documents</p>
          <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Documents You Can Verify
          </h2>
          <p class="mt-4 text-lg leading-relaxed text-slate-600">
            EduCore supports digital document management and verification workflows, including QR-based
            verification for institutional documents.
          </p>
          <ul class="mt-7 space-y-3">
            <li class="flex items-center gap-3 text-slate-700">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                <FolderOpen class="h-4 w-4 text-blue-600" />
              </span>
              Digital document requests and records
            </li>
            <li class="flex items-center gap-3 text-slate-700">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                <FileText class="h-4 w-4 text-blue-600" />
              </span>
              Transcripts, certificates, and institutional records
            </li>
            <li class="flex items-center gap-3 text-slate-700">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50">
                <QrCode class="h-4 w-4 text-sky-600" />
              </span>
              QR-based verification for issued documents
            </li>
          </ul>
          <p class="mt-5 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
            <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold text-white">Planned</span>
            Part of the Documents module — in development.
          </p>
        </Reveal>

        <Reveal from="right" class="relative">
          <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/10 sm:p-7">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <img :src="symbolMark" alt="EduCore symbol" class="h-8 w-8 rounded-lg object-cover" />
                <div>
                  <p class="font-display text-sm font-bold text-slate-900">EduCore</p>
                  <p class="text-xs text-slate-500">University Document</p>
                </div>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold tracking-wider text-slate-500 uppercase">
                Illustrative preview
              </span>
            </div>

            <dl class="mt-5 space-y-3">
              <div v-for="f in fields" :key="f.label" class="flex items-center justify-between border-b border-dashed border-slate-200 pb-2.5">
                <dt class="text-xs font-medium text-slate-400">{{ f.label }}</dt>
                <dd class="text-sm font-semibold text-slate-800">{{ f.value }}</dd>
              </div>
              <div class="flex items-center justify-between pt-1.5">
                <dt class="text-xs font-medium text-slate-400">Status</dt>
                <dd class="flex items-center gap-1.5 text-sm font-semibold text-teal-600">
                  <CheckCircle2 class="h-4 w-4" />
                  Verified
                </dd>
              </div>
            </dl>

            <div class="mt-6 flex items-center gap-5">
              <div class="relative h-28 w-28 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="bg-grid-doc absolute inset-0" aria-hidden="true" />
                <span class="absolute top-1.5 left-1.5 h-5 w-5 rounded-sm border-2 border-slate-900" />
                <span class="absolute top-3 left-3 h-1.5 w-1.5 bg-slate-900" />
                <span class="absolute top-1.5 right-1.5 h-5 w-5 rounded-sm border-2 border-slate-900" />
                <span class="absolute right-3 top-3 h-1.5 w-1.5 bg-slate-900" />
                <span class="absolute bottom-1.5 left-1.5 h-5 w-5 rounded-sm border-2 border-slate-900" />
                <span class="absolute bottom-3 left-3 h-1.5 w-1.5 bg-slate-900" />
                <span class="absolute top-[45%] left-[42%] h-2 w-2 bg-slate-900" />
                <span class="absolute top-[58%] left-[56%] h-2.5 w-2.5 bg-slate-900" />
                <span class="absolute top-[64%] left-[26%] h-1.5 w-1.5 bg-slate-900" />
                <span class="absolute top-[24%] right-[20%] h-2 w-2 bg-slate-900" />
                <span class="absolute bottom-[22%] right-[34%] h-1.5 w-1.5 bg-slate-900" />
                <div
                  class="edu-scan absolute inset-x-4 h-0.5 rounded-full bg-gradient-to-r from-transparent via-blue-600 to-transparent"
                  aria-hidden="true"
                />
              </div>
              <div class="flex flex-col gap-2">
                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                  <ScanLine class="h-4 w-4 text-sky-600" />
                  Verifiable anytime
                </span>
                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                  <BadgeCheck class="h-4 w-4 text-teal-600" />
                  Authenticated record
                </span>
                <p class="mt-1 text-xs text-slate-500">Scan a QR code to confirm a document was issued by the institution.</p>
              </div>
            </div>
          </div>

          <Reveal :delay="160" class="mt-8 flex items-center justify-center gap-2">
            <template v-for="(step, i) in pipeline" :key="step">
              <span class="rounded-full border border-slate-200 bg-slate-50 px-3.5 py-1.5 text-xs font-semibold text-slate-600">
                {{ step }}
              </span>
              <span v-if="i < pipeline.length - 1" class="text-xs text-slate-300">→</span>
            </template>
          </Reveal>
        </Reveal>
      </div>
    </div>
  </section>
</template>