<script setup>
import { Link } from '@inertiajs/vue3'
import { scrollToId } from './scrollTo'

const symbolMark = '/assets/logo/educore-symbol-mark.jpg'
const year = new Date().getFullYear()

const explore = [
  { id: 'platform', label: 'Platform' },
  { id: 'how-it-works', label: 'How It Works' },
  { id: 'modules', label: 'Modules' },
  { id: 'technology', label: 'Technology' },
  { id: 'about', label: 'About' },
]
const platform = [
  { id: 'showcase', label: 'Product tour' },
  { id: 'documents', label: 'Document verification' },
  { id: 'communication', label: 'Communication' },
  { id: 'analytics', label: 'Analytics' },
  { id: 'security', label: 'Security & access' },
]
const account = [
  { href: '/login', label: 'Sign in' },
  { href: '/forgot-password', label: 'Reset your password' },
]

const link = 'rounded-sm text-small text-dark-muted transition-colors duration-150 hover:text-dark-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary'
</script>

<template>
  <footer id="site-footer" class="border-t border-dark-border bg-primary-dark text-dark-ink">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[2fr_1fr_1fr_1fr] lg:px-8">
      <div class="col-span-2 max-w-sm lg:col-span-1">
        <a href="#top" class="inline-flex items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary" aria-label="EduCore — back to top" @click.prevent="scrollToId('top', 0)">
          <img :src="symbolMark" alt="" width="32" height="32" class="h-8 w-8 rounded-md object-cover" />
          <span class="font-display text-h4 font-bold">Edu<span class="text-dark-primary">Core</span></span>
        </a>
        <p class="mt-4 font-display text-body font-semibold">One Platform. Smarter Education.</p>
        <p class="mt-2 text-small text-dark-muted">
          A university digital administration platform for academic management, student services, administration and communication.
        </p>
      </div>

      <nav aria-labelledby="footer-explore">
        <h2 id="footer-explore" class="text-caption font-semibold tracking-widest text-dark-muted uppercase">Explore</h2>
        <ul class="mt-4 space-y-3">
          <li v-for="item in explore" :key="item.id">
            <a :href="`#${item.id}`" :class="link" @click.prevent="scrollToId(item.id, 80)">{{ item.label }}</a>
          </li>
        </ul>
      </nav>

      <nav aria-labelledby="footer-platform">
        <h2 id="footer-platform" class="text-caption font-semibold tracking-widest text-dark-muted uppercase">Platform</h2>
        <ul class="mt-4 space-y-3">
          <li v-for="item in platform" :key="item.id">
            <a :href="`#${item.id}`" :class="link" @click.prevent="scrollToId(item.id, 80)">{{ item.label }}</a>
          </li>
          <li v-for="item in account" :key="item.href">
            <Link :href="item.href" :class="link">{{ item.label }}</Link>
          </li>
        </ul>
      </nav>

      <div class="col-span-2 lg:col-span-1">
        <h2 class="text-caption font-semibold tracking-widest text-dark-muted uppercase">Project</h2>
        <ul class="mt-4 space-y-3 text-small text-dark-muted">
          <li>Thesis and portfolio project</li>
          <li><span class="text-dark-ink">Rin Nairith</span> · Developer</li>
          <li><span class="text-dark-ink">Yong Lyhor</span> · Developer</li>
          <li>Designed for Cambodian universities</li>
        </ul>
      </div>
    </div>

    <div class="border-t border-dark-border">
      <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-caption text-dark-muted sm:flex-row sm:justify-between sm:px-6 lg:px-8">
        <p>© {{ year }} EduCore</p>
        <p>Laravel · Vue · PostgreSQL</p>
      </div>
    </div>
  </footer>
</template>
