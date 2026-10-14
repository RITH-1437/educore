<script setup>
import { Container, Database, Monitor, Server } from '@lucide/vue'
import Reveal from './Reveal.vue'
import { techLogos } from './techLogos'

// The stack actually in use (README, composer.json, package.json, docker-compose).
// Sanctum and Laravel Cloud are Laravel products without a separate mark, so
// they show the Laravel logo. Pinia is registered but holds no stores, and
// the frontend is plain JavaScript, so neither Pinia nor TypeScript is listed.
const groups = [
  {
    name: 'Frontend',
    icon: Monitor,
    items: [
      { logo: 'vue', name: 'Vue 3', role: 'Interface' },
      { logo: 'javascript', name: 'JavaScript', role: 'Language' },
      { logo: 'inertia', name: 'Inertia.js', role: 'Server-driven pages' },
      { logo: 'tailwind', name: 'Tailwind CSS 4', role: 'Styling' },
      { logo: 'chartjs', name: 'Chart.js', role: 'Charts' },
    ],
  },
  {
    name: 'Backend',
    icon: Server,
    items: [
      { logo: 'laravel', name: 'Laravel 12', role: 'Framework, queues, scheduler' },
      { logo: 'php', name: 'PHP 8.4', role: 'Language' },
      { logo: 'laravel', name: 'Laravel Sanctum', role: 'API authentication' },
    ],
  },
  {
    name: 'Data & storage',
    icon: Database,
    items: [
      { logo: 'postgresql', name: 'PostgreSQL 16', role: 'Database' },
      { logo: 'redis', name: 'Redis', role: 'Cache, queues, sessions' },
      { logo: 'minio', name: 'MinIO', role: 'S3-compatible file storage' },
    ],
  },
  {
    name: 'Infrastructure',
    icon: Container,
    items: [
      { logo: 'docker', name: 'Docker Compose', role: 'Development environment' },
      { logo: 'nginx', name: 'Nginx', role: 'Web server' },
      { logo: 'laravel', name: 'Laravel Cloud', role: 'Production target' },
    ],
  },
]
</script>

<template>
  <section id="technology" class="scroll-mt-20 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="technology-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <Reveal class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-16">
        <div class="max-w-2xl">
          <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Technology</p>
          <h2 id="technology-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">Built with Modern Technology</h2>
        </div>
        <p class="max-w-md text-small text-muted dark:text-dark-muted">
          A Laravel and Vue modular monolith: one codebase and one deployment, run in Docker for development, with
          Laravel Cloud as the production target.
        </p>
      </Reveal>

      <div class="mt-10 grid grid-cols-2 gap-x-6 gap-y-10 sm:gap-x-8 lg:grid-cols-4">
        <Reveal v-for="(group, gi) in groups" :key="group.name" :delay="gi * 70">
          <h3 class="flex items-center gap-2 border-b border-border-default pb-3 text-small font-semibold text-primary-dark dark:border-dark-border dark:text-dark-ink">
            <component :is="group.icon" class="h-4 w-4 text-primary dark:text-dark-primary" aria-hidden="true" />
            {{ group.name }}
          </h3>
          <ul class="mt-4 space-y-3">
            <li v-for="tech in group.items" :key="tech.name" class="flex items-center gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary-dark dark:ring-1 dark:ring-dark-border">
                <svg viewBox="0 0 24 24" class="h-5 w-5" :fill="techLogos[tech.logo].hex" aria-hidden="true">
                  <path :d="techLogos[tech.logo].path" />
                </svg>
              </span>
              <span class="min-w-0">
                <span class="block text-small font-semibold text-ink dark:text-dark-ink">{{ tech.name }}</span>
                <span class="block text-caption text-muted dark:text-dark-muted">{{ tech.role }}</span>
              </span>
            </li>
          </ul>
        </Reveal>
      </div>
    </div>
  </section>
</template>
