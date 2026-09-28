<script setup>
import { Head } from '@inertiajs/vue3'
import { BookOpen, CalendarDays, ClipboardCheck, GraduationCap, Sparkles } from '@lucide/vue'
import BaseBadge from '../components/BaseBadge.vue'
import BaseCard from '../components/BaseCard.vue'

defineProps({
  title: { type: String, required: true },
  description: { type: String, required: true },
  areas: { type: Array, default: () => [] },
  role: { type: String, required: true },
  userName: { type: String, required: true },
})

const previews = [
  { label: 'My courses', icon: BookOpen },
  { label: 'Schedule', icon: CalendarDays },
  { label: 'Academic activity', icon: ClipboardCheck },
]
</script>

<template>
  <Head :title="`${title} - EduCore`" />
  <div class="space-y-8">
    <section class="overflow-hidden rounded-xl border border-border-default bg-surface p-6 shadow-sm dark:border-dark-border dark:bg-dark-surface sm:p-8">
      <div class="flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-2xl">
          <BaseBadge variant="primary" dot>Role workspace preview</BaseBadge>
          <h2 class="mt-4 text-h1 font-display font-semibold text-ink dark:text-dark-ink">{{ title }}</h2>
          <p class="mt-3 text-body text-muted dark:text-dark-muted">Welcome, {{ userName }}. {{ description }}</p>
        </div>
        <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
          <GraduationCap class="h-7 w-7" aria-hidden="true" />
        </span>
      </div>
      <div class="mt-6 flex flex-wrap gap-2">
        <BaseBadge variant="muted">{{ role.replaceAll('-', ' ') }}</BaseBadge>
        <BaseBadge variant="warning">Sample dashboard</BaseBadge>
      </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3" aria-label="Dashboard preview areas">
      <BaseCard v-for="(preview, index) in previews" :key="preview.label" class="motion-safe:animate-slide-up" :style="{ animationDelay: `${index * 50}ms` }">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
          <component :is="preview.icon" class="h-5 w-5" aria-hidden="true" />
        </span>
        <p class="mt-4 text-small font-medium text-muted dark:text-dark-muted">{{ preview.label }}</p>
        <p class="mt-1 text-h3 font-semibold text-ink dark:text-dark-ink">—</p>
        <p class="mt-2 text-caption text-muted dark:text-dark-muted">Role-specific data will appear here when this module is implemented.</p>
      </BaseCard>
    </section>

    <BaseCard title="Your workspace" padding="lg">
      <template #description>Planned tools for this role dashboard.</template>
      <ul class="mt-4 grid gap-3 sm:grid-cols-2">
        <li v-for="(area, index) in areas" :key="area" class="flex items-start gap-3 rounded-lg border border-border-default p-4 dark:border-dark-border">
          <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-pill bg-background text-caption font-semibold text-muted dark:bg-dark-surface-2 dark:text-dark-muted">{{ index + 1 }}</span>
          <span class="text-small font-medium text-ink dark:text-dark-ink">{{ area }}</span>
        </li>
      </ul>
      <div class="mt-6 flex items-start gap-3 rounded-lg bg-primary/5 p-4 text-small text-muted dark:bg-dark-primary/10 dark:text-dark-muted">
        <Sparkles class="mt-0.5 h-5 w-5 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
        <p>This is a sample dashboard layout. Features and records will be added in the upcoming module phases.</p>
      </div>
    </BaseCard>
  </div>
</template>
