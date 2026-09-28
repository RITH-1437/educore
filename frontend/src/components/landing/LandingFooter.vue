<script setup>
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'

const nav = [
  { id: 'top', label: 'Home' },
  { id: 'platform', label: 'Platform' },
  { id: 'modules', label: 'Modules' },
  { id: 'technology', label: 'Technology' },
  { id: 'about', label: 'About' },
]

const team = ['Rin Nairith', 'Lyhor']
</script>

<template>
  <footer class="border-t border-white/10 bg-[#020617] py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid gap-10 lg:grid-cols-[1.2fr_1fr_1fr]">
        <div>
          <div class="flex items-center gap-2.5">
            <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-white">
              <img :src="symbolMark" alt="EduCore symbol" class="h-9 w-9 object-cover" />
            </span>
            <div>
              <p class="font-display text-lg font-bold text-white">EduCore</p>
              <p class="text-xs text-slate-400">One Platform. Smarter Education.</p>
            </div>
          </div>
          <p class="mt-5 max-w-sm text-sm leading-relaxed text-slate-400">
            A university digital administration platform connecting academic management, student
            services, administration, communication, documents, and institutional workflows.
          </p>
        </div>

        <nav aria-label="Landing navigation">
          <h3 class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase">Navigation</h3>
          <ul class="mt-4 space-y-2.5">
            <li v-for="n in nav" :key="n.id">
              <button
                type="button"
                class="text-sm text-slate-400 transition hover:text-sky-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400"
                @click="scrollToId(n.id, 0)"
              >
                {{ n.label }}
              </button>
            </li>
          </ul>
        </nav>

        <div>
          <h3 class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase">Team</h3>
          <ul class="mt-4 space-y-2.5">
            <li v-for="m in team" :key="m" class="text-sm text-slate-400">{{ m }}</li>
          </ul>
        </div>
      </div>

      <div class="mt-14 flex flex-col items-start justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row sm:items-center">
        <p class="text-sm text-slate-500">© 2026 EduCore. All rights reserved.</p>
        <p class="text-sm text-slate-500">University Digital Administration Platform</p>
      </div>
    </div>
  </footer>
</template>