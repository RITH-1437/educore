<script setup>
import { Container, Monitor, Pause, Play, Server } from '@lucide/vue'
import { ref } from 'vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'
import { techLogos } from './techLogos'

// The stack actually in use (see README / docs/1). Sanctum and Laravel Cloud
// are Laravel products without a separate mark, so they show the Laravel logo.
const groups = [
  {
    name: 'Frontend',
    icon: Monitor,
    duration: '45s',
    items: [
      { logo: 'vue', name: 'Vue 3', role: 'UI framework' },
      { logo: 'javascript', name: 'JavaScript', role: 'Language' },
      { logo: 'tailwind', name: 'Tailwind CSS', role: 'Styling' },
      { logo: 'pinia', name: 'Pinia', role: 'State' },
      { logo: 'inertia', name: 'Inertia.js', role: 'Page routing' },
      { logo: 'chartjs', name: 'Chart.js', role: 'Charts' },
    ],
  },
  {
    name: 'Backend',
    icon: Server,
    duration: '35s',
    items: [
      { logo: 'laravel', name: 'Laravel', role: 'Framework' },
      { logo: 'php', name: 'PHP', role: 'Language' },
      { logo: 'laravel', name: 'Sanctum', role: 'API authentication' },
    ],
  },
  {
    name: 'Infrastructure',
    icon: Container,
    duration: '50s',
    items: [
      { logo: 'postgresql', name: 'PostgreSQL', role: 'Database' },
      { logo: 'redis', name: 'Redis', role: 'Cache and queues' },
      { logo: 'docker', name: 'Docker', role: 'Containers' },
      { logo: 'minio', name: 'MinIO', role: 'File storage' },
      { logo: 'nginx', name: 'Nginx', role: 'Web server' },
      { logo: 'laravel', name: 'Laravel Cloud', role: 'Deployment' },
    ],
  },
]

// One copy holds at least six tiles so it is wider than the row; the track is
// two copies, and the animation slides by exactly one copy for a seamless loop.
// Only the first `items.length` tiles are read by screen readers.
const track = (items) => {
  let copy = [...items]
  while (copy.length < 6) copy = copy.concat(items)
  return [...copy, ...copy]
}

const paused = ref(false)
</script>

<template>
  <section id="technology" class="scroll-mt-24 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="technology-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="technology-title"
        eyebrow="Technology"
        title="A proven, open-source stack"
        description="EduCore is a Laravel and Vue modular monolith, run in Docker for development and deployed to Laravel Cloud."
      />

      <Reveal class="mt-12 space-y-4" :class="{ 'edu-marquee-paused': paused }">
        <div class="flex justify-end motion-reduce:hidden">
          <button
            type="button"
            class="inline-flex min-h-10 items-center gap-2 rounded-md border border-border-default bg-surface px-3 text-label text-ink transition-colors duration-150 hover:bg-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2"
            :aria-pressed="paused"
            @click="paused = !paused"
          >
            <component :is="paused ? Play : Pause" class="h-4 w-4" aria-hidden="true" />
            {{ paused ? 'Play' : 'Pause' }} animation
          </button>
        </div>

        <div v-for="(group, gi) in groups" :key="group.name" class="grid items-center gap-3 lg:grid-cols-[10rem_1fr] lg:gap-6">
          <h3 class="flex items-center gap-2 text-small font-semibold text-primary-dark dark:text-dark-ink">
            <component :is="group.icon" class="h-5 w-5 text-primary dark:text-dark-primary" aria-hidden="true" />
            {{ group.name }}
          </h3>

          <div class="edu-marquee-row edu-marquee-fade overflow-hidden">
            <ul
              class="edu-marquee flex w-max"
              :class="{ 'edu-marquee-reverse': gi % 2 === 1 }"
              :style="{ '--marquee-duration': group.duration }"
              :aria-label="`${group.name} technologies`"
            >
              <li
                v-for="(tech, i) in track(group.items)"
                :key="`${group.name}-${i}`"
                class="pr-3 pb-3"
                :class="{ 'edu-marquee-copy': i >= group.items.length }"
                :aria-hidden="i >= group.items.length || undefined"
              >
                <div class="group flex w-56 items-center gap-3 rounded-lg border border-border-default bg-background p-3 transition-[border-color,box-shadow] duration-150 ease-out hover:border-border-muted hover:shadow-sm dark:border-dark-border dark:bg-dark-bg dark:hover:border-dark-muted">
                  <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary-dark dark:ring-1 dark:ring-dark-border">
                    <svg
                      viewBox="0 0 24 24"
                      class="h-5 w-5 transition-transform duration-150 ease-out group-hover:scale-110"
                      :fill="techLogos[tech.logo].hex"
                      aria-hidden="true"
                    >
                      <path :d="techLogos[tech.logo].path" />
                    </svg>
                  </span>
                  <span class="min-w-0">
                    <span class="block truncate text-small font-semibold text-ink dark:text-dark-ink">{{ tech.name }}</span>
                    <span class="block truncate text-caption text-muted dark:text-dark-muted">{{ tech.role }}</span>
                  </span>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </Reveal>

    </div>
  </section>
</template>
